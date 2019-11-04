<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageSource;
use SimpleSAML\Module\perun\model\Facility;

class XmlMetadataToPerun
{
    const PROXY_IDENTIFIER = 'proxyIdentifier';

    const FLATFILE_ATTRIBUTES = 'flatfile2internal';

    const XML_ATTRIBUTES = 'xml2internal';

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
        $this->flatfileAttributes = $conf->getArray(self::FLATFILE_ATTRIBUTES, []);
        $this->xmlAttributes = $conf->getArray(self::XML_ATTRIBUTES, []);
    }

    /**
     * Convert SSP metadata array to Perun facility array.
     * @param array $metadata
     * @return array facility
     */
    public function metadataToFacility(array $metadata)
    {
        $facility = [];
        if (isset($metadata['AssertionConsumerService'])) {
            $facility['assertionConsumerService'] = self::getEndpoint(
                $metadata['AssertionConsumerService'],
                'HTTP-POST'
            );
        }
        if (isset($metadata['SingleLogoutService'])) {
            $facility['singleLogoutService'] = self::getEndpoint(
                $metadata['SingleLogoutService'],
                'HTTP-Redirect'
            );
        }
        if (!empty($metadata['keys'])) {
            $certData = self::getCertData($metadata['keys']);
            $facility['signingCert'] = $certData['signing'];
            $facility['encryptionCert'] = $certData['encryption'];
        }
        if (!empty($metadata['contacts'])) {
            $contacts = self::getEmailsByType($metadata['contacts']);
            if (!empty($contacts['administrative']) || !empty($contacts['technical'])) {
                $facility['spAdminContact'] = array_merge(
                    $contacts['administrative'] ?? [],
                    $contacts['technical'] ?? []
                );
            }
            if (!empty($contacts['support'])) {
                $facility['spSupportContact'] = $contacts['support'];
            }
        }

        $this->addArrayAttributes($metadata, $facility);
        $this->addXmlAttributes($metadata, $facility);

        $facility['proxyIdentifiers'] = [$this->proxyIdentifier];
        $facility['masterProxyIdentifier'] = $this->proxyIdentifier;

        return $facility;
    }

    /**
     * Load XML metadata file and get SPs.
     * @param string $filename
     * @return array metadata
     */
    public function createFacilityFromXml(string $filename)
    {
        $sps = MetaDataStorageSource::getSource(['type' => 'xml', 'file' => $filename])
            ->getMetadataSet('saml20-sp-remote');

        return array_map([$this, 'metadataToFacility'], $sps);
    }

    /**
     * Create facility in Perun and set its attributes based on the facility array.
     * @param array $info - facility array
     * @return boolean true on success
     */
    public function createFacilityWithAttributes(array $info)
    {
        if (empty($info['entityID'])) {
            throw new \Exception('Missing entityID');
        }

        $facilities = $this->adapter->getFacilitiesByEntityId($info['entityID']);
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

        $attribute_names = array_keys($info);
        $attributes = $this->getAttributesDefinition($attribute_names);
        if (empty($attributes) || count($attributes) !== count($attribute_names)) {
            //throw new \Exception('Did not get all attributes from Perun');
            echo 'Missing some of these attributes: ' . print_r($attribute_names, true) . "\n";
        }
        foreach ($attributes as $i => $attribute) {
            $value = $info[$attribute['friendlyName']];
            if (!is_array($value) && substr($attribute['type'], -4) === 'List') {
                $value = [$value];
            }
            $attributes[$i]['value'] = $value;
        }
        $this->setFacilityAttributes($facility, $attributes);

        return true;
    }

    /**
     * Convert XML metadata file to facilities in Perun.
     * @param string $filename
     */
    public function convertXml($filename)
    {
        $facilities = $this->createFacilityFromXml($filename);
        foreach ($facilities as $facility) {
            $this->createFacilityWithAttributes($facility);
        }
    }

    private function addArrayAttributes($metadata, &$facility)
    {
        foreach ($this->flatfileAttributes as $perunAttribute => $metadataAttribute) {
            $indexes = is_array($metadataAttribute) ? $metadataAttribute : [$metadataAttribute];
            $t = self::getNestedAttribute($metadata, $indexes);
            if ($t !== null) {
                $facility[$perunAttribute] = $t;
            }
        }
    }

    private function addXmlAttributes($metadata, &$facility)
    {
        $xml = base64_decode($metadata['entityDescriptor'], true);
        $xml = new \SimpleXMLElement($xml);
        foreach ($this->xmlAttributes as $perunAttribute => $xpath) {
            $result = $xml->xpath(is_array($xpath) ? $xpath[0] : $xpath);
            if ($result !== false && count($result) > 0) {
                $facility[$perunAttribute] = is_array($xpath) ? array_map('strval', $result) : $result[0];
            }
        }
    }

    private function getAttributesDefinition(array $attrNames)
    {
        return array_values(
            array_filter(
                $this->adapter->getAttributesDefinition(),
                function ($attr) use ($attrNames) {
                    return in_array($attr['friendlyName'], $attrNames, true);
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
        if (empty($info['entityID'])) {
            return false;
        }
        return preg_replace('~[^-_\.a-zA-Z0-9]~', '_', $info['entityID']);
    }

    private function createFacility(array $info)
    {
        $facility = ['facility' => [
            'name' => self::generateFacilityName($info),
            'description' => $info['description'] ?? '',
        ]];
        $this->adapter->createFacility($facility);
    }

    private static function getEndpoint($endpoints, string $binding)
    {
        if (empty($endpoints)) {
            return null;
        }
        if (!is_array($endpoints)) {
            return [$endpoints];
        }
        $result = [];
        foreach ($endpoints as $endpoint) {
            if ($endpoint['Binding'] === 'urn:oasis:names:tc:SAML:2.0:bindings:' . $binding) {
                $result[] = $endpoint['Location'];
            }
        }
        return $result;
    }

    private static function getCertData(array $keys)
    {
        $certData = [
            'encryption' => [],
            'signing' => [],
        ];
        foreach ($keys as $key) {
            if ($key['type'] === 'X509Certificate' && !empty($key['X509Certificate'])) {
                if ($key['signing']) {
                    $certData['signing'][] = $key['X509Certificate'];
                }
                if ($key['encryption']) {
                    $certData['encryption'][] = $key['X509Certificate'];
                }
            }
        }
        return $certData;
    }

    private static function getNestedAttribute(array $array, array $indexes)
    {
        foreach ($indexes as $index) {
            if (!isset($array[$index])) {
                return null;
            }
            $array = $array[$index];
        }
        return $array;
    }

    private static function getEmailsByType(array $contacts)
    {
        $result = [];
        foreach ($contacts as $contact) {
            if (isset($contact['contactType']) && !empty($contact['emailAddress'])) {
                if (!isset($result[$contact['contactType']])) {
                    $result[$contact['contactType']] = [];
                }
                $result[$contact['contactType']] = array_merge(
                    $result[$contact['contactType']],
                    $contact['emailAddress']
                );
            }
        }
        return $result;
    }
}
