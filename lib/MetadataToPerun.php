<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageSource;
use SimpleSAML\Module\perun\model\Facility;

/**
 * Get metadata and save them in Perun.
 */
class MetadataToPerun
{
    public const ENTITY_ID = 'entityID';

    public const SERVICE_NAME = 'serviceName';

    public const SERVICE_DESCRIPTION = 'serviceDescription';

    public const ORGANIZATION_NAME = 'organizationName';

    public const ORGANIZATION_DESCRIPTION = 'organizationDescription';

    public const PROXY_IDENTIFIER = 'proxyIdentifier';

    public const PERUN_PROXY_IDENTIFIER_ATTR_NAME = 'perunProxyIdentifierAttr';

    public const PERUN_MASTER_PROXY_IDENTIFIER_ATTR_NAME = 'perunMasterProxyIdentifierAttr';

    public const PERUN_IS_SAML_FACILITY_ATTR_NAME = 'perunIsSamlFacilityAttr';

    public const FLATFILE_ATTRIBUTES = 'flatfile2internal';

    public const XML_ATTRIBUTES = 'xml2internal';

    public const PERUN_ATTRIBUTES = 'internal2perun';

    public const TRANSFORMERS = 'importTransformers';

    public const METADATA_SET = 'saml20-sp-remote';

    public const NAMESPACE_SEPARATOR = ':';

    public const XPATH_LANG = 'ancestor-or-self::*[attribute::xml:lang][1]/@xml:lang';

    public const ENTITY_ID_REMOVE = [
        '~^https?://~',
        '~(_sp)?_shibboleth$~',
        '~_shibboleth_sp$~',
        '~_entity$~',
        '~_secure$~',
        '~_shibboleth_cztestfed_sp$~',
        '~_$~',
    ];

    /**
     * @var AdapterRpc
     */
    private $adapter;

    private $proxyIdentifier;

    /**
     * The contructor.
     */
    public function __construct()
    {
        $this->adapter = new AdapterRpc();
        $conf = Configuration::getConfig(MetadataFromPerun::CONFIG_FILE_NAME);
        $this->proxyIdentifier = $conf->getString(self::PROXY_IDENTIFIER);
        $this->proxyIdentifiersAttr = $conf->getString(self::PERUN_PROXY_IDENTIFIER_ATTR_NAME);
        $this->masterProxyIdentifierAttr = $conf->getString(self::PERUN_MASTER_PROXY_IDENTIFIER_ATTR_NAME);
        $this->isSamlFacilityAttr = $conf->getString(self::PERUN_IS_SAML_FACILITY_ATTR_NAME, '');
        $this->flatfileAttributes = $conf->getArray(self::FLATFILE_ATTRIBUTES, []);
        $this->xmlAttributes = $conf->getArray(self::XML_ATTRIBUTES, []);
        $this->perunAttributes = $conf->getArray(self::PERUN_ATTRIBUTES, []);
        $this->transformers = $conf->getArray(self::TRANSFORMERS, []);
        $this->transformers = array_map(function ($transformer) {
            $class = $transformer['class'];
            $t = new $class(Configuration::loadFromArray($transformer['config'] ?? []));
            return [
                'instance' => $t,
                'attributes' => $transformer['attributes'],
            ];
        }, $this->transformers);
    }

    /**
     * Convert SSP metadata array to Perun facility array.
     *
     * @return array facility or null if a transformer deleted entityID
     * @see \SimpleSAML\Module\perun\AttributeTransformer
     */
    public function metadataToFacility(array $metadata)
    {
        $facility = [];

        $this->addArrayAttributes($metadata, $facility);
        $this->addXmlAttributes($metadata, $facility);

        foreach ($this->transformers as $transformer) {
            $attrs = array_intersect_key($facility, array_flip($transformer['attributes']));
            if (! empty($attrs)) {
                $newAttrs = $transformer['instance']->transform($attrs);
                $facility = array_merge($facility, $newAttrs);
                if (! isset($facility[self::ENTITY_ID]) || $facility[self::ENTITY_ID] === null) {
                    return null;
                }
            }
        }

        return $facility;
    }

    /**
     * Load XML metadata file and get SPs.
     *
     * @return array metadata
     * @see getFacilitiesFrom()
     */
    public function getFacilitiesFromXml(string $filename)
    {
        return $this->getFacilitiesFrom([
            'type' => 'xml',
            'file' => $filename,
        ]);
    }

    /**
     * Load flatfile metadata and get SPs.
     *
     * @param string $directory
     * @return array metadata
     * @see getFacilitiesFrom()
     */
    public function getFacilitiesFromFlatfile(string $directory = null)
    {
        $config = [
            'type' => 'flatfile',
        ];
        if ($directory !== null) {
            $config['directory'] = $directory;
        }
        return $this->getFacilitiesFrom($config);
    }

    /**
     * Load metadata and get SPs. See MetaDataStorageSource and subclasses for details.
     *
     * @param array $config - config for MetaDataStorageSource::getSource (type, file, directory...)
     * @return array metadata
     * @see https://github.com/simplesamlphp/simplesamlphp/blob/master/lib/SimpleSAML/Metadata/MetaDataStorageSource.php
     */
    public function getFacilitiesFrom(array $config)
    {
        $sps = MetaDataStorageSource::getSource($config)->getMetadataSet(self::METADATA_SET);

        return array_filter(array_map([$this, 'metadataToFacility'], $sps));
    }

    /**
     * Create facility in Perun and set its attributes based on the facility array.
     *
     * @param array $info - facility array
     * @return boolean true on success
     */
    public function createFacilityWithAttributes(array $info)
    {
        if (empty($info[self::ENTITY_ID])) {
            throw new \Exception('Missing entityID');
        }

        $facilities = $this->adapter->getFacilitiesByEntityId($info[self::ENTITY_ID]);
        switch (count($facilities)) {
            case 0:
                $facility = $this->createFacility($info);
                break;
            case 1:
                $facility = $facilities[0];
                break;
            default:
                throw new \Exception('AssertionError');
        }

        $attributes = $this->getAttributesDefinition(array_merge(
            array_keys($this->perunAttributes),
            [$this->proxyIdentifiersAttr, $this->masterProxyIdentifierAttr],
            $this->isSamlFacilityAttr ? [$this->isSamlFacilityAttr] : []
        ));
        if (empty($attributes)) {
            throw new \Exception('Did not get attribute definitions from Perun');
        }
        foreach ($attributes as $i => $attribute) {
            $perunName = $attribute['namespace'] . self::NAMESPACE_SEPARATOR . $attribute['friendlyName'];
            if (isset($this->perunAttributes[$perunName])) {
                $internalName = $this->perunAttributes[$perunName];
                $value = $info[$internalName] ?? null;
                if ($value !== null) {
                    if (! is_array($value) && substr($attribute['type'], -4) === 'List') {
                        $value = [$value];
                    }
                    $attributes[$i]['value'] = $value;
                }
            } elseif ($perunName === $this->masterProxyIdentifierAttr) {
                $attributes[$i]['value'] = $this->proxyIdentifier;
            } elseif ($this->isSamlFacilityAttr !== '' && $perunName === $this->isSamlFacilityAttr) {
                $attributes[$i]['value'] = true;
            }
            if ($perunName === $this->proxyIdentifiersAttr) {
                $attributes[$i]['value'][] = $this->proxyIdentifier;
            }
        }
        $this->setFacilityAttributes($facility, $attributes);

        return true;
    }

    /**
     * Convert XML metadata file to facilities in Perun.
     *
     * @param string $filename
     */
    public function convertXml($filename)
    {
        $facilities = $this->getFacilitiesFromXml($filename);
        foreach ($facilities as $facility) {
            $this->createFacilityWithAttributes($facility);
        }
    }

    private function addArrayAttributes($metadata, &$facility)
    {
        foreach ($this->flatfileAttributes as $perunAttribute => $metadataAttribute) {
            $indexes = is_array($metadataAttribute) ? $metadataAttribute : [$metadataAttribute];
            foreach ($indexes as $index) {
                $t = self::getNestedAttribute($metadata, explode('.', $index));
                if ($t !== null) {
                    $facility[$perunAttribute] = $t;
                }
            }
        }
    }

    private function addXmlAttributes($metadata, &$facility)
    {
        $xml = base64_decode($metadata['entityDescriptor'], true);
        $xml = new \SimpleXMLElement($xml);
        foreach ($this->xmlAttributes as $perunAttribute => $xpath) {
            if (is_string($xpath)) {
                $result = $xml->xpath($xpath);
                $result = $result !== false && count($result) > 0 ? $result[0] : false;
            } elseif (count($xpath) !== 1) {
                throw new \Exception('xpath array should have exactly 1 item');
            } else {
                $index = key($xpath);
                $xpathSelector = $xpath[$index];
                $result = $xml->xpath($xpathSelector);
                if ($result !== false && count($result) > 0) {
                    if (is_string($index)) {
                        $indexes = array_map(function ($el) use ($index) {
                            $i = $el->xpath($index);
                            return $i !== false && count($i) > 0 ? ((string) $i[0]) : false;
                        }, $result);
                        if (in_array(false, $indexes, true) || count($indexes) !== count($result)) {
                            throw new \Exception('Did not find corresponding number of keys using xpath ' . $index);
                        }
                        $result = array_combine(array_map('strval', $indexes), array_map('strval', $result));
                    // TODO: multiple keys same
                    } else {
                        $result = array_map('strval', $result);
                    }
                } else {
                    $result = false;
                }
            }

            if ($result !== false) {
                $facility[$perunAttribute] = $result;
            }
        }
    }

    private function getAttributesDefinition(array $attrNames)
    {
        $perunAttrNames = AttributeUtils::getAttrNames($attrNames, AttributeUtils::RPC);
        return array_values(
            array_filter(
                $this->adapter->getAttributesDefinition(),
                function ($attr) use ($perunAttrNames) {
                    $perunName = $attr['namespace'] . self::NAMESPACE_SEPARATOR . $attr['friendlyName'];
                    return in_array($perunName, $perunAttrNames, true);
                }
            )
        );
    }

    private function setFacilityAttributes($facility, array $attributes)
    {
        $this->adapter->setFacilityAttributes(is_array($facility) ? $facility['id'] : $facility->getId(), $attributes);
    }

    /**
     * @return string|boolean
     */
    private static function generateFacilityName(array $info)
    {
        if (empty($info[self::ENTITY_ID])) {
            return false;
        }
        return self::escapeFacilityName(
            'SP_' .
            self::stringOrEnglishOrAny(
                $info[self::SERVICE_NAME]
                ?? $info[self::ORGANIZATION_NAME]
                ?? preg_replace(self::ENTITY_ID_REMOVE, '', $info[self::ENTITY_ID])
            )
        );
    }

    /**
     * @return string
     */
    private static function escapeFacilityName($str)
    {
        return preg_replace('~[^-_\.a-zA-Z0-9]~', '_', $str);
    }

    /**
     * @return string
     */
    private static function stringOrEnglishOrAny($strOrEn)
    {
        if (is_string($strOrEn)) {
            return $strOrEn;
        }
        if (isset($strOrEn['en'])) {
            return $strOrEn['en'];
        }
        return current($strOrEn);
    }

    private function createFacility(array $info)
    {
        $facility = [
            'facility' => [
                'name' => self::generateFacilityName($info),
                'description' => self::stringOrEnglishOrAny(
                    $info[self::SERVICE_DESCRIPTION] ?? $info[self::ORGANIZATION_DESCRIPTION] ?? ''
                ),
            ],
        ];
        return $this->adapter->createFacility($facility);
    }

    private static function getNestedAttribute(array $array, array $indexes)
    {
        foreach ($indexes as $index) {
            if (! isset($array[$index])) {
                return null;
            }
            $array = $array[$index];
        }
        return $array;
    }
}
