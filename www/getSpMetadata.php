<?php

use SimpleSAML\Configuration;
use SimpleSAML\Module\perun\AdapterRpc;

const CONFIG_FILE_NAME = 'module_perun_getMetadata.php';

const PERUN_PROXY_IDENTIFIER_ATTR_NAME = 'perunProxyIdentifierAttr';
const PERUN_PROXY_ENTITY_ID_ATTR_NAME = 'perunProxyEntityIDAttr';

const PROXY_IDENTIFIER = 'proxyIdentifier';
const ABSOLUTE_FILE_NAME = 'absoluteFileName';
const ATTRIBUTES_DEFINITIONS = 'attributesDefinitions';
const FACILITY_ATTRIBUTES = 'facilityAttributes';

const TYPE_INTEGER = 'java.lang.Integer';
const TYPE_BOOLEAN = 'java.lang.Boolean';
const TYPE_STRING = 'java.lang.String';
const TYPE_ARRAY = 'java.util.ArrayList';
const TYPE_MAP = 'java.util.LinkedHashMap';

$conf = Configuration::getConfig(CONFIG_FILE_NAME);

$proxyIdentifier = $conf->getString(PROXY_IDENTIFIER);
assert($proxyIdentifier === null || empty($proxyIdentifier));

$attributesDefinitions = $conf->getArray(ATTRIBUTES_DEFINITIONS);
assert($attributesDefinitions === null || is_array($attributesDefinitions));

$perunProxyIdentifierAttr = $conf->getString(PERUN_PROXY_IDENTIFIER_ATTR_NAME);
$perunProxyEntityIDAttr = $conf->getString(PERUN_PROXY_ENTITY_ID_ATTR_NAME);
assert($perunProxyEntityIDAttr === null || empty($perunProxyEntityIDAttr) ||
    $perunProxyIdentifierAttr === null || empty($perunProxyIdentifierAttr));

$absoluteFileName = $conf->getString(ABSOLUTE_FILE_NAME);
assert($absoluteFileName === null || empty($absoluteFileName));

$rpcAdapter = new AdapterRpc();

// Get list of all attribute names
$attrNames = [];
$allAttrNames = [];
array_push($allAttrNames, $perunProxyEntityIDAttr);
foreach ($attributesDefinitions as $key => $value) {
    array_push($attrNames, $key);
    array_push($allAttrNames, $key);
}

// Get all facilities with proxyIdentifiers
$attributeDefinition = [];
$attributeDefinition[$perunProxyIdentifierAttr] = $proxyIdentifier;
$facilities = $rpcAdapter->searchFacilitiesByAttributeValue($attributeDefinition);

// Get facilities with attributes
$facilitiesWithAttributes = [];
foreach ($facilities as $facility) {
    $attributes = $rpcAdapter->getFacilityAttributes($facility, $allAttrNames);
    $facilityAttributes = [];
    foreach ($attributes as $attribute) {
        $facilityAttributes[$attribute['name']] = $attribute;
    }
    $facilitiesWithAttributes[$facility->getId()] = [
        'facility' => $facility,
        FACILITY_ATTRIBUTES => $facilityAttributes,
    ];
}

// Generate array with metadata
$content = '<?php' . PHP_EOL;
foreach ($facilitiesWithAttributes as $facilityWithAttributes) {
    $metadataContent = '';
    if (
        isset($facilityWithAttributes[FACILITY_ATTRIBUTES][$perunProxyEntityIDAttr]) &&
        !empty($facilityWithAttributes[FACILITY_ATTRIBUTES][$perunProxyEntityIDAttr]['value'])
    ) {
        $metadataContent .= '$metadata[\'' .
            $facilityWithAttributes[FACILITY_ATTRIBUTES][$perunProxyEntityIDAttr]['value'] . '\'] = [' . PHP_EOL;
        foreach ($attributesDefinitions as $perunAttrName => $metadataAttrName) {
            $attribute = $facilityWithAttributes[FACILITY_ATTRIBUTES][$perunAttrName];
            if (
                ($attribute['type'] === TYPE_INTEGER) &&
                is_numeric($attribute['value']) &&
                $attribute['value'] !== null
            ) {
                $metadataContent .= "\t '" . $metadataAttrName . "' => " . $attribute['value'] . ',' . PHP_EOL;
            } elseif (($attribute['type'] === TYPE_STRING) && $attribute['value'] !== null) {
                $metadataContent .= "\t '" . $metadataAttrName . "' => '" . $attribute['value'] . "'," . PHP_EOL;
            } elseif ($attribute['type'] === TYPE_BOOLEAN) {
                $metadataContent .= "\t '" . $metadataAttrName . "' => ";
                if ($attribute['value'] === null || $attribute['value'] === 'false') {
                    $metadataContent .= 'false,' . PHP_EOL;
                } else {
                    $metadataContent .= 'true,' . PHP_EOL;
                }
            } elseif ($attribute['type'] === TYPE_ARRAY && $attribute['value'] !== null) {
                $metadataContent .= "\t '" . $metadataAttrName . "' => [" . PHP_EOL;
                foreach ($attribute['value'] as $value) {
                    $metadataContent .= "\t\t'" . $value . "'," . PHP_EOL;
                }
                $metadataContent .= "\t]," . PHP_EOL;
            } elseif ($attribute['type'] === TYPE_MAP && $attribute['value'] !== null) {
                $metadataContent .= "\t '" . $metadataAttrName . "' => [" . PHP_EOL;
                foreach ($attribute['value'] as $key => $value) {
                    $metadataContent .= "\t\t'" . $key . "' => '" . $value . "'," . PHP_EOL;
                }
                $metadataContent .= "\t]," . PHP_EOL;
            }
        }
        $metadataContent .= '];' . PHP_EOL . "\n";
    }
    $content .= $metadataContent;
}

file_put_contents($absoluteFileName, $content, LOCK_EX);

if (file_exists($absoluteFileName)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($absoluteFileName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($absoluteFileName));
    readfile($absoluteFileName);
    exit;
}
