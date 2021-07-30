<?php

declare(strict_types=1);

/**
 * Script for updating UES in separate thread
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baranek <baranek@ics.muni.cz>
 */

use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\ChallengeManager;

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

try {
    $challengeManager = new ChallengeManager();
    $claims = $challengeManager->decodeToken($token);

    $attributesFromIdP = $claims['data']['attributes'];
    $attrMap = $claims['data']['attrMap'];
    $attrsToConversion = $claims['data']['attrsToConversion'];
    $perunUserId = $claims['data']['perunUserId'];
    $id = $claims['id'];
} catch (Exception $ex) {
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
            $attributesFromIdP['sourceIdPEntityID'][0] . ' and Login ' .
            $attributesFromIdP['sourceIdPEppn'][0]
        );
    }

    $attributesFromPerunRaw = $adapter->getUserExtSourceAttributes($userExtSource['id'], array_keys($attrMap));
    $attributesFromPerun = [];
    foreach ($attributesFromPerunRaw as $attributeFromPerunRaw) {
        $attributesFromPerun[$attributeFromPerunRaw['name']] = $attributeFromPerunRaw;
    }

    if ($attributesFromPerun === null) {
        throw new Exception('perun/www/updateUes.php: getting attributes was not successful.');
    }

    $attributesToUpdate = [];

    foreach ($attributesFromPerun as $attribute) {
        $attrName = $attribute['name'];

        if (isset($attrMap[$attrName], $attributesFromIdP[$attrMap[$attrName]])) {
            $attr = $attributesFromIdP[$attrMap[$attrName]];

            if (in_array($attrName, $attrsToConversion, true)) {
                $arrayAsString = [''];
                foreach ($attr as $value) {
                    $arrayAsString[0] .= $value . ';';
                }
                if (! empty($arrayAsString[0])) {
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
                throw new Exception('perun/www/updateUes.php: unsupported type of attribute.');
            }
            if ($valueFromIdP !== $attribute['value']) {
                $attribute['value'] = $valueFromIdP;
                $attribute['namespace'] = UES_ATTR_NMS;
                array_push($attributesToUpdate, $attribute);
            }
        }
    }

    $attributesToUpdateFinal = [];
    if (! empty($attributesToUpdate)) {
        foreach ($attributesToUpdate as $attribute) {
            $attribute['name'] = UES_ATTR_NMS . ':' . $attribute['friendlyName'];
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
