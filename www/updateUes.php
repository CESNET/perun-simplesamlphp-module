<?php

declare(strict_types=1);

/**
 * Script for updating UES in separate thread.
 */

use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\ChallengeManager;

const CLASS_PREFIX = 'perun/www/updateUes.php: ';
const CONFIG_FILE_NAME = 'module_perun.php';
const CONFIG_SECTION = 'updateUes';
const SOURCE_IDP_ATTRIBUTE_KEY = 'sourceIdPAttributeKey';

const USER_IDENTIFIERS = 'userIdentifiers';
const SOURCE_IDP_ENTITY_ID = 'sourceIdPEntityID';

const NAME = 'name';
const FRIENDLY_NAME = 'friendlyName';
const ID = 'id';
const VALUE = 'value';
const NAMESPACE_KEY = 'namespace';
const UES_ATTR_NMS = 'urn:perun:ues:attribute-def:def';

const TYPE = 'type';
const STRING_TYPE = 'String';
const INTEGER_TYPE = 'Integer';
const BOOLEAN_TYPE = 'Boolean';
const ARRAY_TYPE = 'Array';
const MAP_TYPE = 'Map';

const DATA = 'data';
const ATTRIBUTES = 'attributes';
const ATTR_MAP = 'attrMap';
const ATTR_TO_CONVERSION = 'attrsToConversion';
const PERUN_USER_ID = 'perunUserId';

const EDU_PERSON_UNIQUE_ID = 'eduPersonUniqueId';
const EDU_PERSON_PRINCIPAL_NAME = 'eduPersonPrincipalName';
const EDU_PERSON_TARGETED_ID = 'eduPersonTargetedID';
const NAMEID = 'nameid';
const UID = 'uid';

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
$sourceIdpAttributeKey = null;

try {
    $challengeManager = new ChallengeManager();
    $claims = $challengeManager->decodeToken($token);

    $attributesFromIdP = $claims[DATA][ATTRIBUTES];
    $attrMap = $claims[DATA][ATTR_MAP];
    $attrsToConversion = $claims[DATA][ATTR_TO_CONVERSION];
    $perunUserId = $claims[DATA][PERUN_USER_ID];
    $id = $claims[ID];
} catch (Exception $ex) {
    Logger::error(CLASS_PREFIX . 'The token verification ended with an error.');
    http_response_code(400);
    exit;
}

try {
    $config = Configuration::getConfig(CONFIG_FILE_NAME);
    $config = $config->getArray(CONFIG_SECTION, null);
} catch (Exception $e) {
    $config = null;
}

if (null === $config) {
    Logger::warning(CLASS_PREFIX . 'Configuration is missing. Using default values');
}

$sourceIdpAttributeKey = empty($config[SOURCE_IDP_ATTRIBUTE_KEY]) ? SOURCE_IDP_ENTITY_ID : $config[SOURCE_IDP_ATTRIBUTE_KEY];

if (null !== $config && !empty($config[USER_IDENTIFIERS] && is_array($config[USER_IDENTIFIERS]))) {
    $userIdentifiers = $config[USER_IDENTIFIERS];
} else {
    $userIdentifiers = [EDU_PERSON_UNIQUE_ID, EDU_PERSON_PRINCIPAL_NAME, EDU_PERSON_TARGETED_ID, NAMEID, UID];
}

try {
    if (empty($attributesFromIdP[$sourceIdpAttributeKey][0])) {
        throw new Exception(CLASS_PREFIX . 'Invalid attributes from Idp - \'' . $sourceIdpAttributeKey . '\' is empty');
    }

    $extSourceName = $attributesFromIdP[$sourceIdpAttributeKey][0];
    Logger::debug(CLASS_PREFIX . 'Extracted extSourceName: \'' . $extSourceName . '\'');

    $userExtSource = findUserExtSource($adapter, $extSourceName, $attributesFromIdP, $userIdentifiers);
    if (null === $userExtSource) {
        throw new Exception(
            CLASS_PREFIX . 'There is no UserExtSource that could be used for user ' . $perunUserId . ' and ExtSource ' . $attributesFromIdP[$sourceIdpAttributeKey][0]
        );
    }

    $attributesFromPerun = getAttributesFromPerun($adapter, $attrMap, $userExtSource);
    $attributesToUpdate = getAttributesToUpdate($attributesFromPerun, $attrMap, $attrsToConversion, $attributesFromIdP);

    if (updateUserExtSource($adapter, $userExtSource, $attributesToUpdate)) {
        Logger::debug(CLASS_PREFIX . 'Updating UES for user with userId: ' . $perunUserId . ' was successful.');
    }
} catch (\Exception $ex) {
    Logger::warning(
        CLASS_PREFIX . 'Updating UES for user with userId: ' . $perunUserId . ' was not successful: ' .
        $ex->getMessage()
    );
}

function findUserExtSource($adapter, $extSourceName, $attributes, $userIdentifiers)
{
    foreach ($attributes as $attrName => $attrValue) {
        if (!in_array($attrName, $userIdentifiers, true)) {
            Logger::debug(CLASS_PREFIX . 'Identifier \'' . $attrName . '\' not listed in userIdentifiers. Skipping');
            continue;
        }

        if (is_array($attrValue)) {
            foreach ($attrValue as $extLogin) {
                $userExtSource = getUserExtSource($adapter, $extSourceName, $extLogin);

                if (null !== $userExtSource) {
                    return $userExtSource;
                }
            }
        } elseif (is_string($attrValue)) {
            $userExtSource = getUserExtSource($adapter, $attrValue, $extLogin);

            if (null !== $userExtSource) {
                return $userExtSource;
            }
        }
    }

    return null;
}

function getUserExtSource($adapter, $extSourceName, $extLogin)
{
    try {
        return $adapter->getUserExtSource($extSourceName, $extLogin);
    } catch (SimpleSAML\Module\perun\Exception $ex) {
        Logger::debug(CLASS_PREFIX . 'Caught exception when fetching user ext source, probably does not exist.');
        Logger::debug(CLASS_PREFIX . $ex->getMessage());

        return null;
    }
}

function getAttributesFromPerun($adapter, $attrMap, $userExtSource): array
{
    $attributesFromPerunRaw = $adapter->getUserExtSourceAttributes($userExtSource[ID], array_keys($attrMap));
    $attributesFromPerun = [];

    foreach ($attributesFromPerunRaw as $rawAttribute) {
        if (!empty($rawAttribute[NAME])) {
            $attributesFromPerun[$rawAttribute[NAME]] = $rawAttribute;
        }
    }

    if (null === $attributesFromPerun) {
        throw new Exception(CLASS_PREFIX . 'Getting attributes was not successful.');
    }

    return $attributesFromPerun;
}

function getAttributesToUpdate($attributesFromPerun, $attrMap, $attrsToConversion, $attributesFromIdP): array
{
    $attributesToUpdate = [];

    foreach ($attributesFromPerun as $attribute) {
        $attrName = $attribute[NAME];

        $mappedAttributeName = !empty($attrMap[$attrName]) ? $attrMap[$attrName] : null;
        $idpAttribute = !empty($attributesFromIdP[$attrMap[$attrName]]) ?
            $attributesFromIdP[$attrMap[$attrName]] : null;

        if (null !== $mappedAttributeName && null !== $idpAttribute) {
            if (in_array($attrName, $attrsToConversion, true)) {
                $idpAttribute = serializeAsString($idpAttribute);
            }

            if (isSimpleType($attribute[TYPE])) {
                $valueFromIdP = $idpAttribute[0];
            } elseif (isComplexType($attribute[TYPE])) {
                $valueFromIdP = $idpAttribute;
            } else {
                throw new Exception(CLASS_PREFIX . 'Unsupported type of attribute.');
            }

            if ($valueFromIdP !== $attribute[VALUE]) {
                $attribute[VALUE] = $valueFromIdP;
                $attribute[NAMESPACE_KEY] = UES_ATTR_NMS;
                $attributesToUpdate[] = $attribute;
            }
        }
    }

    return $attributesToUpdate;
}

function updateUserExtSource($adapter, $userExtSource, $attributesToUpdate): bool
{
    $attributesToUpdateFinal = [];

    if (!empty($attributesToUpdate)) {
        foreach ($attributesToUpdate as $attribute) {
            $attribute[NAME] = UES_ATTR_NMS . ':' . $attribute[FRIENDLY_NAME];
            $attributesToUpdateFinal[] = $attribute;
        }

        $adapter->setUserExtSourceAttributes($userExtSource[ID], $attributesToUpdateFinal);
    }

    $adapter->updateUserExtSourceLastAccess($userExtSource[ID]);

    return true;
}

function isSimpleType($attributeType): bool
{
    return strpos($attributeType, STRING_TYPE)
    || strpos($attributeType, INTEGER_TYPE)
    || strpos($attributeType, BOOLEAN_TYPE);
}

function isComplexType($attributeType): bool
{
    return strpos($attributeType, ARRAY_TYPE) ||
        strpos($attributeType, MAP_TYPE);
}

function serializeAsString($idpAttribute)
{
    $arrayAsString = [''];

    foreach ($idpAttribute as $value) {
        $arrayAsString[0] .= $value . ';';
    }

    if (!empty($arrayAsString[0])) {
        $arrayAsString[0] = substr($arrayAsString[0], 0, -1);
    }

    return $arrayAsString;
}
