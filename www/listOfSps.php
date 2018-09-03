<?php

const CONFIG_FILE_NAME = 'module_perun_listOfSps.php';
const PROXY_IDENTIFIER = 'proxyIdentifier';
const ATTRIBUTES_DEFINITIONS = 'attributesDefinitions';
const PERUN_PROXY_IDENTIFIER_ATTR_NAME = 'perunProxyIdentifierAttr';

$config = SimpleSAML_Configuration::getInstance();
$conf = SimpleSAML_Configuration::getConfig(CONFIG_FILE_NAME);

$proxyIdentifier = $conf->getString(PROXY_IDENTIFIER);
assert(is_null($proxyIdentifier) || empty($proxyIdentifier));
$attributesDefinitions = $conf->getArray(ATTRIBUTES_DEFINITIONS);
$perunProxyIdentifierAttr = $conf->getString(PERUN_PROXY_IDENTIFIER_ATTR_NAME);
assert(is_null($attributesDefinitions) || is_array($attributesDefinitions));

$rpcAdapter = new sspmod_perun_AdapterRpc();
$attributeDefinition = array();
$attributeDefinition[$perunProxyIdentifierAttr] = $proxyIdentifier;
$facilities = $rpcAdapter->searchFacilitiesByAttributeValue($attributeDefinition);

$attrNames = array();
foreach ($attributesDefinitions as $attributeDefinition) {
		array_push($attrNames, $attributeDefinition);
}

$facilitiesWithAttributes = array();
foreach ($facilities as $facility) {
	$attributes = $rpcAdapter->getFacilityAttributes($facility, $attrNames);
	$facilityAttributes = array();
	foreach ($attributes as $attribute) {
		$facilityAttributes[$attribute['name']] = $attribute;
	}
	$facilitiesWithAttributes[$facility->getId()] = array(
		'facility' => $facility,
		'facilityAttributes' => $facilityAttributes
	);
}

$t = new SimpleSAML_XHTML_Template($config, 'perun:listOfSps-tpl.php');
$t->data['attrNames'] = $attrNames;
$t->data['facilitiesWithAttributes'] = $facilitiesWithAttributes;
$t->show();
