<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;

class MetadataFromPerun
{
    const CONFIG_FILE_NAME = 'module_perun_getMetadata.php';

    const PERUN_PROXY_IDENTIFIER_ATTR_NAME = 'perunProxyIdentifierAttr';

    const PERUN_PROXY_ENTITY_ID_ATTR_NAME = 'perunProxyEntityIDAttr';

    const PROXY_IDENTIFIER = 'proxyIdentifier';

    const ABSOLUTE_FILE_NAME = 'absoluteFileName';

    const ATTRIBUTES_DEFINITIONS = 'attributesDefinitions';

    const FACILITY_ATTRIBUTES = 'facilityAttributes';

    const PERUN_PROXY_CERT_ATTR_NAME = 'perunProxyCertAttr';

    const STRING_IF_SINGLE = ['AssertionConsumerService', 'SingleLogoutService', 'NameIDFormat'];

    const TRANSFORMERS = 'exportTransformers';

    private $perunProxyEntityIDAttr;

    private $attributesDefinitions;

    private $rpcAdapter;

    private $conf;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->perunProxyEntityIDAttr = $this->conf->getString(self::PERUN_PROXY_ENTITY_ID_ATTR_NAME);
        $this->attributesDefinitions = $this->conf->getArray(self::ATTRIBUTES_DEFINITIONS);
        $this->certAttributes = $this->conf->getArray(self::PERUN_PROXY_CERT_ATTR_NAME, []);
        $this->rpcAdapter = new AdapterRpc();
    }

    /**
     * Get metadata array from facility (with attributes).
     */
    public function getMetadata($facility)
    {
        if (
            !isset($facility[self::FACILITY_ATTRIBUTES][$this->perunProxyEntityIDAttr]) ||
            empty($facility[self::FACILITY_ATTRIBUTES][$this->perunProxyEntityIDAttr]['value'])
        ) {
            return null;
        }
        $id = $facility[self::FACILITY_ATTRIBUTES][$this->perunProxyEntityIDAttr]['value'];
        $metadata = [];
        foreach ($this->attributesDefinitions as $perunAttrName => $metadataAttrName) {
            $attribute = $facility[self::FACILITY_ATTRIBUTES][$perunAttrName];
            if ($attribute['value'] !== null) {
                $metadata[$metadataAttrName] = $attribute['value'];
            }
        }

        foreach ($this->conf->getArray(self::TRANSFORMERS, []) as $transformer) {
            $class = $transformer['class'];
            $t = new $class();
            $attrs = array_intersect_key($metadata, array_flip($transformer['attributes']));
            if (!empty($attrs)) {
                $newAttrs = $t->transform($attrs, $transformer['config']);
                $metadata = array_merge($metadata, $newAttrs);
            }
        }

        //$this->addComplexAttributes($facility, $metadata);
        return [$id => $metadata];
    }

    /**
     * Get metadata array for all facilities.
     */
    public function getAllMetadata()
    {
        $metadata = [];
        foreach ($this->getFacilitiesWithAttributes() as $facility) {
            $metadata = array_merge($metadata, $this->getMetadata($facility));
        }
        return $metadata;
    }

    /**
     * Get metadata in the flatfile format for all facilities.
     * @uses SimpleSAML\Module\perun\MetadataFromPerun::metadataToFlatfile
     * @uses SimpleSAML\Module\perun\MetadataFromPerun::getAllMetadata
     */
    public function getAllMetadataAsFlatfile()
    {
        return self::metadataToFlatfile($this->getAllMetadata());
    }

    /**
     * Generate array with metadata.
     * @see https://github.com/simplesamlphp/simplesamlphp/blob/master/www/admin/metadata-converter.php
     */
    public static function metadataToFlatfile($metadata)
    {
        $flatfile = '<?php' . PHP_EOL;
        foreach ($metadata as $entityId => $entityMetadata) {
            $flatfile .= '$metadata[' . var_export($entityId, true) . '] = '
            . var_export($entityMetadata, true) . ";\n";
        }
        return $flatfile;
    }

    /**
     * Save content in the configured file and force its download.
     * @param string $content
     */
    public function saveAndDownload($content)
    {
        $absoluteFileName = $this->conf->getString(self::ABSOLUTE_FILE_NAME);
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
    }

    /**
     * @todo If key has both signing and encryption, do not add it twice
     */
    private function addComplexAttributes($facility, array &$metadata)
    {
        $keys = [];
        foreach ($this->certAttributes as $purpose => $attrName) {
            if (!empty($facility[self::FACILITY_ATTRIBUTES][$attrName]['value'])) {
                $keys = array_merge(
                    $keys,
                    self::formatKeys($facility[self::FACILITY_ATTRIBUTES][$attrName]['value'], $purpose)
                );
            }
        }
        if ($keys) {
            $metadata['keys'] = $keys;
        }
    }

    private static function formatKeys(array $keys, string $purpose)
    {
        return array_map(function ($key) use ($purpose) {
            return [
                'type' => 'X509Certificate',
                'X509Certificate' => $key,
                $purpose => true,
            ];
        }, $keys);
    }

    /**
     * Get list of all attribute names.
     */
    private function getAllAttributeNames()
    {
        $allAttrNames = [];
        array_push($allAttrNames, $this->perunProxyEntityIDAttr);
        foreach (array_keys($this->attributesDefinitions) as $attr) {
            array_push($allAttrNames, $attr);
        }
        foreach ($this->certAttributes as $attr) {
            array_push($allAttrNames, $attr);
        }
        return $allAttrNames;
    }

    /**
     * Get all facilities with proxyIdentifiers.
     */
    private function getFacilities()
    {
        $perunProxyIdentifierAttr = $this->conf->getString(self::PERUN_PROXY_IDENTIFIER_ATTR_NAME);
        $proxyIdentifier = $this->conf->getString(self::PROXY_IDENTIFIER);
        $attributeDefinition = [
            $perunProxyIdentifierAttr => $proxyIdentifier,
        ];
        return $this->rpcAdapter->searchFacilitiesByAttributeValue($attributeDefinition);
    }

    /**
     * Get facilities with attributes.
     */
    private function getFacilitiesWithAttributes()
    {
        $allAttrNames = $this->getAllAttributeNames();
        $facilitiesWithAttributes = [];
        foreach ($this->getFacilities() as $facility) {
            $attributes = $this->rpcAdapter->getFacilityAttributes($facility, $allAttrNames);
            $facilityAttributes = [];
            foreach ($attributes as $attribute) {
                $facilityAttributes[$attribute['name']] = $attribute;
            }
            $facilitiesWithAttributes[$facility->getId()] = [
                'facility' => $facility,
                self::FACILITY_ATTRIBUTES => $facilityAttributes,
            ];
        }
        return $facilitiesWithAttributes;
    }
}
