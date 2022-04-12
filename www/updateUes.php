<?php

declare(strict_types=1);

/**
 * Script for updating UES in separate thread.
 */

use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\ChallengeManager;

const DEBUG_PREFIX = 'perun/www/updateUes.php: ';
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
const APPEND_ONLY_ATTRS = 'appendOnlyAttrs';
const PERUN_USER_ID = 'perunUserId';

const EDU_PERSON_UNIQUE_ID = 'eduPersonUniqueId';
const EDU_PERSON_PRINCIPAL_NAME = 'eduPersonPrincipalName';
const EDU_PERSON_TARGETED_ID = 'eduPersonTargetedID';
const NAMEID = 'nameid';
const UID = 'uid';

function getDefaultConfig(): array
{
    return [
        SOURCE_IDP_ATTRIBUTE_KEY => SOURCE_IDP_ENTITY_ID,
        USER_IDENTIFIERS => [EDU_PERSON_UNIQUE_ID, EDU_PERSON_PRINCIPAL_NAME, EDU_PERSON_TARGETED_ID, NAMEID, UID],
    ];
}

function getConfiguration()
{
    $config = getDefaultConfig();
    try {
        $configuration = Configuration::getConfig(CONFIG_FILE_NAME);
        $localConfig = $configuration->getArray(CONFIG_SECTION, null);
        if (!empty($localConfig)) {
            $config = $localConfig;
        } else {
            Logger::warning(DEBUG_PREFIX . 'Configuration is missing. Using default values');
        }
    } catch (Exception $e) {
        Logger::warning(DEBUG_PREFIX . 'Configuration is invalid. Using default values');
        //OK, we will use the default config
    }

    return $config;
}

$adapter = Adapter::getInstance(Adapter::RPC);
$token = file_get_contents('php://input');

if (empty($token)) {
    http_response_code(400);
    exit('The entity body is empty');
}

$attributesFromIdP = null;
$attrMap = null;
$serializedAttributes = [];
$appendOnlyAttrs = [];
$perunUserId = null;
$id = null;
$sourceIdpAttribute = null;

try {
    $challengeManager = new ChallengeManager();
    $claims = $challengeManager->decodeToken($token);

    $attributesFromIdP = $claims[DATA][ATTRIBUTES];
    $attrMap = $claims[DATA][ATTR_MAP];
    $serializedAttributes = $claims[DATA][ATTR_TO_CONVERSION];
    $appendOnlyAttrs = $claims[DATA][APPEND_ONLY_ATTRS];
    $perunUserId = $claims[DATA][PERUN_USER_ID];
    $id = $claims[ID];
} catch (Exception $ex) {
    Logger::error(DEBUG_PREFIX . 'The token verification ended with an error.');
    http_response_code(400);
    exit;
}

$config = getConfiguration();

$sourceIdpAttribute = $config[SOURCE_IDP_ATTRIBUTE_KEY];
$identifierAttributes = $config[USER_IDENTIFIERS];

try {
    if (empty($attributesFromIdP[$sourceIdpAttribute][0])) {
        throw new Exception(
            DEBUG_PREFIX . 'Invalid attributes from IdP - Attribute \'' . $sourceIdpAttribute . '\' is empty'
        );
    }

    $extSourceName = $attributesFromIdP[$sourceIdpAttribute][0];
    Logger::debug(DEBUG_PREFIX . 'Extracted extSourceName: \'' . $extSourceName . '\'');

    $userExtSource = findUserExtSource($adapter, $extSourceName, $attributesFromIdP, $identifierAttributes);
    if (null === $userExtSource) {
        throw new Exception(
            DEBUG_PREFIX . 'There is no UserExtSource that could be used for user ' . $perunUserId . ' and IdP ' . $extSourceName
        );
    }

    $attributesFromPerun = getAttributesFromPerun($adapter, $attrMap, $userExtSource);
    $attributesToUpdate = getAttributesToUpdate(
        $attributesFromPerun,
        $attrMap,
        $serializedAttributes,
        $appendOnlyAttrs,
        $attributesFromIdP
    );

    if (updateUserExtSource($adapter, $userExtSource, $attributesToUpdate)) {
        Logger::debug(DEBUG_PREFIX . 'Updating UES for user with userId: ' . $perunUserId . ' was successful.');
    }
} catch (\Exception $ex) {
    Logger::warning(
        DEBUG_PREFIX . 'Updating UES for user with userId: ' . $perunUserId . ' was not successful: ' .
        $ex->getMessage()
    );
}

function findUserExtSource($adapter, $extSourceName, $attributesFromIdp, $identifierAttributes)
{
    foreach ($attributesFromIdp as $attrName => $attrValue) {
        if (!in_array($attrName, $identifierAttributes, true)) {
            continue;
        }

        if (!is_array($attrValue)) {
            $attrValue = [$attrValue];
        }

        foreach ($attrValue as $extLogin) {
            $userExtSource = getUserExtSource($adapter, $extSourceName, $extLogin);

            if (null !== $userExtSource) {
                Logger::debug(
                    DEBUG_PREFIX . 'Found user ext source for combination extSourceName \''
                    . $extSourceName . '\' and extLogin \'' . $extLogin . '\''
                );

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
        return null;
    }
}

function getAttributesFromPerun($adapter, $attrMap, $userExtSource): array
{
    $attributesFromPerun = [];
    $attributesFromPerunRaw = $adapter->getUserExtSourceAttributes($userExtSource[ID], array_keys($attrMap));
    if (empty($attributesFromPerunRaw)) {
        throw new Exception(DEBUG_PREFIX . 'Getting attributes for UES was not successful.');
    }

    foreach ($attributesFromPerunRaw as $rawAttribute) {
        if (!empty($rawAttribute[NAME])) {
            $attributesFromPerun[$rawAttribute[NAME]] = $rawAttribute;
        }
    }

    if (empty($attributesFromPerun)) {
        throw new Exception(DEBUG_PREFIX . 'Getting attributes for UES was not successful.');
    }

    return $attributesFromPerun;
}

function getAttributesToUpdate($attributesFromPerun, $attrMap, $serializedAttributes, $appendOnlyAttrs,  $attributesFromIdP): array
{
    $attributesToUpdate = [];

    foreach ($attributesFromPerun as $attribute) {
        $attrName = $attribute[NAME];

        $attr = !empty($attributesFromIdP[$attrMap[$attrName]]) ?
            $attributesFromIdP[$attrMap[$attrName]] : [];

        // appendOnly && has value && (complex || serialized)
        if (in_array($attrName, $appendOnlyAttrs, true) &&
            !empty($attribute[VALUE]) &&
            (isComplexType($attribute[TYPE]) ||  in_array($attrName, $serializedAttributes, true))
        ) {
            $attr = in_array($attrName, $serializedAttributes, true) ?
                array_merge($attr, explode(';', $attribute[VALUE])) : array_merge($attr, $attribute[VALUE]);
        }


        if (isSimpleType($attribute[TYPE])) {
            $newValue = convertToString($attr);
        } elseif (isComplexType($attribute[TYPE])) {
            if (!empty($attr)) {
                $newValue = array_values(array_unique($attr));
            } else {
                $newValue = [];
            }
            if (in_array($attrName, $serializedAttributes, true)) {
                $newValue = convertToString($newValue);
            }
        } else {
            Logger::debug(DEBUG_PREFIX . 'Unsupported type of attribute.');
            continue;
        }

        if ($newValue !== $attribute[VALUE]) {
            $attribute[VALUE] = $newValue;
            $attribute[NAMESPACE_KEY] = UES_ATTR_NMS;
            $attributesToUpdate[] = $attribute;
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

function convertToString($newValue)
{
    if (!empty($newValue)) {
        $newValue = array_unique($newValue);
        $attrValueAsString = implode(';', $newValue);
    } else {
        $attrValueAsString = '';
    }

    return $attrValueAsString;
}
