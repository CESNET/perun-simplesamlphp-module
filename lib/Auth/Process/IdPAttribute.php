<?php

/**
 * Class sspmod_perun_Auth_Process_IdPAttribute
 *
 * This class for each line in $attrMAp search the $key in IdP Metadata and save it to $request['Attributes'][$value]
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class sspmod_perun_Auth_Process_IdPAttribute extends SimpleSAML_Auth_ProcessingFilter
{
    private $attrMap;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert('is_array($config)');

        if (!isset($config['attrMap'])) {
            throw new SimpleSAML_Error_Exception(
                "perun:IdPAttribute: missing mandatory configuration option 'attrMap'."
            );
        }

        $this->attrMap = (array)$config['attrMap'];
    }

    public function process(&$request)
    {
        assert('is_array($request)');

        $metadataHandler = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
        $sourceIdpMeta = $metadataHandler->getMetaData($request['saml:sp:IdP'], 'saml20-idp-remote');

        foreach ($this->attrMap as $attributeKey => $attributeValue) {
            $attributeNames = preg_split('/:/', $attributeKey);
            $key = array_shift($attributeNames);

            if (!isset($sourceIdpMeta[$key])) {
                continue;
            }
            $value = $sourceIdpMeta[$key];

            foreach ($attributeNames as $attributeName) {
                if (!isset($value[$attributeName])) {
                    continue;
                }
                $value = $value[$attributeName];
            }

            if (!is_array($value)) {
                $value = array($value);
            }

            if (!empty($value)) {
                $request['Attributes'][$attributeValue] = $value;
            }
        }
    }
}
