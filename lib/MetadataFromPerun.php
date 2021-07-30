<?php

declare(strict_types=1);

/**
 * @author Pavel Vyskocil
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use Symfony\Component\VarExporter\VarExporter;

class MetadataFromPerun
{
    public const CONFIG_FILE_NAME = 'module_perun_getMetadata.php';

    public const PERUN_PROXY_IDENTIFIER_ATTR_NAME = 'perunProxyIdentifierAttr';

    public const PERUN_PROXY_ENTITY_ID_ATTR_NAME = 'perunProxyEntityIDAttr';

    public const PROXY_IDENTIFIER = 'proxyIdentifier';

    public const ABSOLUTE_FILE_NAME = 'absoluteFileName';

    public const ATTRIBUTES_DEFINITIONS = 'attributesDefinitions';

    public const FACILITY_ATTRIBUTES = 'facilityAttributes';

    public const TRANSFORMERS = 'exportTransformers';

    private $perunProxyEntityIDAttr;

    private $attributesDefinitions;

    private $rpcAdapter;

    private $conf;

    public function __construct()
    {
        $this->conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->perunProxyEntityIDAttr = $this->conf->getString(self::PERUN_PROXY_ENTITY_ID_ATTR_NAME);
        $this->attributesDefinitions = $this->conf->getArray(self::ATTRIBUTES_DEFINITIONS);
        $this->rpcAdapter = new AdapterRpc();
    }

    /**
     * Get metadata array from facility (with attributes).
     */
    public function getMetadata($facility)
    {
        if (
            ! isset($facility[self::FACILITY_ATTRIBUTES][$this->perunProxyEntityIDAttr]) ||
            empty($facility[self::FACILITY_ATTRIBUTES][$this->perunProxyEntityIDAttr]['value'])
        ) {
            return null;
        }
        $id = $facility[self::FACILITY_ATTRIBUTES][$this->perunProxyEntityIDAttr]['value'];
        $metadata = [];
        foreach ($this->attributesDefinitions as $perunAttrName => $metadataAttrName) {
            $attribute = $facility[self::FACILITY_ATTRIBUTES][$perunAttrName];
            if ($attribute['value'] !== null) {
                if ($attribute['value'] !== null) {
                    $target = &$metadata;
                    $keys = explode('>', $metadataAttrName);
                    while (count($keys) > 1) {
                        $key = array_shift($keys);
                        if (! isset($target[$key])) {
                            $target[$key] = [];
                        }
                        $target = &$target[$key];
                    }
                    $target[$keys[0]] = $attribute['value'];
                }
            }
        }

        foreach ($this->conf->getArray(self::TRANSFORMERS, []) as $transformer) {
            $class = $transformer['class'];
            $t = new $class(Configuration::loadFromArray($transformer['config']));
            $attrs = array_intersect_key($metadata, array_flip($transformer['attributes']));
            if (! empty($attrs)) {
                $newAttrs = $t->transform($attrs);
                $metadata = array_merge($metadata, $newAttrs);
            }
        }

        $metadata = array_filter($metadata, function ($value) {
            return $value !== null;
        });

        return [
            $id => $metadata,
        ];
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
     *
     * @uses SimpleSAML\Module\perun\MetadataFromPerun::metadataToFlatfile
     * @uses SimpleSAML\Module\perun\MetadataFromPerun::getAllMetadata
     */
    public function getAllMetadataAsFlatfile()
    {
        return self::metadataToFlatfile($this->getAllMetadata());
    }

    /**
     * Generate array with metadata.
     *
     * @see https://github.com/simplesamlphp/simplesamlphp/blob/master/www/admin/metadata-converter.php
     */
    public static function metadataToFlatfile($metadata)
    {
        $flatfile = '<?php' . PHP_EOL;
        foreach ($metadata as $entityId => $entityMetadata) {
            $flatfile .= '$metadata[' . var_export($entityId, true) . '] = '
            . VarExporter::export($entityMetadata) . ";\n";
        }
        return $flatfile;
    }

    /**
     * Save content in the configured file and force its download.
     *
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
     * Get list of all attribute names.
     */
    private function getAllAttributeNames()
    {
        $allAttrNames = [];
        array_push($allAttrNames, $this->perunProxyEntityIDAttr);
        foreach (array_keys($this->attributesDefinitions) as $attr) {
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
        $perunProxyIdentifierRpcAttrName = AttributeUtils::getRpcAttrName($perunProxyIdentifierAttr);
        $proxyIdentifier = $this->conf->getString(self::PROXY_IDENTIFIER);
        $attributeDefinition = [
            $perunProxyIdentifierRpcAttrName => $proxyIdentifier,
        ];
        return $this->rpcAdapter->searchFacilitiesByAttributeValue($attributeDefinition);
    }

    /**+
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
            if (! empty($facilityAttributes[$this->perunProxyEntityIDAttr]['value'])) {
                $facilitiesWithAttributes[$facility->getId()] = [
                    'facility' => $facility,
                    self::FACILITY_ATTRIBUTES => $facilityAttributes,
                ];
            }
        }
        return $facilitiesWithAttributes;
    }
}
