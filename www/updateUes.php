<?php

/**
 * Script for updating UES in separate thread
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baranek <baranek@ics.muni.cz>
 */

use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\DatabaseConnector;
use SimpleSAML\Module\perun\ScriptsUtils;

$adapter = Adapter::getInstance(Adapter::RPC);
$token = file_get_contents('php://input');

if (empty($token)) {
    http_response_code(400);
    exit('The entity body is empty');
}

$attributesFromIdP = null;
$attrMap = null;
$attrsToConversion = null;
$perunUserId = null;
$id = null;

const UES_ATTR_NMS = 'urn:perun:ues:attribute-def:def';
const CONFIG_FILE_NAME = 'keys.php';

try {
    $config = Configuration::getConfig(CONFIG_FILE_NAME);
    $keyPub = $config->getString('updateUes');

    $algorithmManager = new AlgorithmManager([new RS512()]);
    $jwsVerifier = new JWSVerifier($algorithmManager);
    $jwk = JWKFactory::createFromKeyFile($keyPub);

    $serializerManager = new JWSSerializerManager([new CompactSerializer()]);
    $jws = $serializerManager->unserialize($token);

    $headerCheckerManager = new HeaderCheckerManager([new AlgorithmChecker(['RS512'])], [new JWSTokenSupport()]);
    $headerCheckerManager->check($jws, 0);

    $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);

    if (!$isVerified) {
        Logger::error('Perun.updateUes: The token signature is invalid!');
        http_response_code(401);
        exit;
    }

    $claimCheckerManager = new ClaimCheckerManager(
        [
            new Checker\IssuedAtChecker(),
            new Checker\NotBeforeChecker(),
            new Checker\ExpirationTimeChecker(),
        ]
    );

    $claims = json_decode($jws->getPayload(), true);
    $claimCheckerManager->check($claims);

    $challenge = $claims['challenge'];

    $attributesFromIdP = $claims['data']['attributes'];
    $attrMap = $claims['data']['attrMap'];
    $attrsToConversion = $claims['data']['attrsToConversion'];
    $perunUserId = $claims['data']['perunUserId'];
    $id = $claims['id'];

    $databaseConnector = new DatabaseConnector();

    $conn = $databaseConnector->getConnection();

    $challengeDb = ScriptsUtils::readChallengeFromDb($conn, $id);
    $checkAccessSucceeded = ScriptsUtils::checkAccess($conn, $challenge, $challengeDb);
    $challengeSuccessfullyDeleted = ScriptsUtils::deleteChallengeFromDb($conn, $id);

    $conn->close();

    if (!$checkAccessSucceeded || !$challengeSuccessfullyDeleted) {
        exit;
    }
} catch (Checker\InvalidClaimException | Checker\MissingMandatoryClaimException $ex) {
    Logger::error('Perun.updateUes: An error occurred when the token was verifying.');
    http_response_code(400);
    exit;
}

try {
    $userExtSource = $adapter->getUserExtSource(
        $attributesFromIdP['sourceIdPEntityID'][0],
        $attributesFromIdP['sourceIdPEppn'][0]
    );
    if ($userExtSource === null) {
        throw new Exception(
            'perun/www/updateUes.php: there is no UserExtSource with ExtSource ' .
            $attributesFromIdP['sourceIdPEntityID'][0] . " and Login " .
            $attributesFromIdP['sourceIdPEppn'][0]
        );
    }

    $attributesFromPerunRaw = $adapter->getUserExtSourceAttributes($userExtSource['id'], array_keys($attrMap));
    $attributesFromPerun = [];
    foreach ($attributesFromPerunRaw as $attributeFromPerunRaw) {
        $attributesFromPerun[$attributeFromPerunRaw['name']] = $attributeFromPerunRaw;
    }

    if ($attributesFromPerun === null) {
        throw new Exception(
            'perun/www/updateUes.php: getting attributes was not successful.'
        );
    }

    $attributesToUpdate = [];

    foreach ($attributesFromPerun as $attribute) {

        $attrName = $attribute['name'];

        if (isset($attrMap[$attrName], $attributesFromIdP[$attrMap[$attrName]])) {
            $attr = $attributesFromIdP[$attrMap[$attrName]];

            if (in_array($attrName, $attrsToConversion)) {
                $arrayAsString = [''];
                foreach ($attr as $value) {
                    $arrayAsString[0] .= $value . ';';
                }
                if (!empty($arrayAsString[0])) {
                    $arrayAsString[0] = substr($arrayAsString[0], 0, -1);
                }
                $attr = $arrayAsString;
            }

            if (strpos($attribute['type'], 'String') ||
                strpos($attribute['type'], 'Integer') ||
                strpos($attribute['type'], 'Boolean')) {
                $valueFromIdP = $attr[0];
            } elseif (strpos($attribute['type'], 'Array') || strpos($attribute['type'], 'Map')) {
                $valueFromIdP = $attr;
            } else {
                throw new Exception(
                    'perun/www/updateUes.php: unsupported type of attribute.'
                );
            }
            if ($valueFromIdP !== $attribute['value']) {
                $attribute['value'] = $valueFromIdP;
                $attribute['namespace'] = UES_ATTR_NMS;
                array_push($attributesToUpdate, $attribute);
            }
        }
    }

    $attributesToUpdateFinal = [];
    if (!empty($attributesToUpdate)) {
        foreach ($attributesToUpdate as $attribute) {
            $attribute['name'] = UES_ATTR_NMS . ":" . $attribute['friendlyName'];
            array_push($attributesToUpdateFinal, $attribute);
        }
        $adapter->setUserExtSourceAttributes($userExtSource['id'], $attributesToUpdateFinal);
    }

    $adapter->updateUserExtSourceLastAccess($userExtSource['id']);

    Logger::debug('perun/www/updateUes.php: Updating UES for user with userId: ' . $perunUserId . ' was successful.');
} catch (\Exception $ex) {
    Logger::warning(
        'perun/www/updateUes.php: Updating UES for user with userId: ' . $perunUserId . ' was not successful: ' .
        $ex->getMessage()
    );
}
