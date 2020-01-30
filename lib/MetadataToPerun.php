<?php

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
    const ENTITY_ID = 'entityID';

    const SERVICE_NAME = 'serviceName';

    const SERVICE_DESCRIPTION = 'serviceDescription';

    const ORGANIZATION_NAME = 'organizationName';

    const ORGANIZATION_DESCRIPTION = 'organizationDescription';

    const PROXY_IDENTIFIER = 'proxyIdentifier';

    const PERUN_PROXY_IDENTIFIER_ATTR_NAME = 'perunProxyIdentifierAttr';

    const PERUN_MASTER_PROXY_IDENTIFIER_ATTR_NAME = 'perunMasterProxyIdentifierAttr';

    const PERUN_IS_SAML_FACILITY_ATTR_NAME = 'perunIsSamlFacilityAttr';

    const FLATFILE_ATTRIBUTES = 'flatfile2internal';

    const XML_ATTRIBUTES = 'xml2internal';

    const PERUN_ATTRIBUTES = 'internal2perun';

    const TRANSFORMERS = 'importTransformers';

    const METADATA_SET = 'saml20-sp-remote';

    const NAMESPACE_SEPARATOR = ':';

    const XPATH_LANG = 'ancestor-or-self::*[attribute::xml:lang][1]/@xml:lang';

    const ENTITY_ID_REMOVE = [
    	'~^https?://~',
    	'~(_sp)?_shibboleth$~',
    	'~_shibboleth_sp$~',
    	'~_entity$~',
    	'~_secure$~',
    	'~_shibboleth_cztestfed_sp$~',
    	'~_$~'
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
            return ['instance' => $t, 'attributes' => $transformer['attributes']];
        }, $this->transformers);
    }

    /**
     * Convert SSP metadata array to Perun facility array.
     * @param array $metadata
     * @return array facility or null if a transformer deleted entityID
     * @see \SimpleSAML\Module\perun\AttributeTransformer
     */
    public function metadataToFacility(array $metadata)
    {
        $facility = [];

        $this->addArrayAttributes($metadata, $facility);
        $this->addXmlAttributes($metadata, $facility);

        foreach ($this->transformers as $transformer) {
            $attrs = array_filter(array_intersect_key($facility, array_flip($transformer['attributes'])));
            if (!empty($attrs)) {
                $newAttrs = $transformer['instance']->transform($attrs);
                $facility = array_merge($facility, $newAttrs);
                if (!isset($facility[self::ENTITY_ID]) || $facility[self::ENTITY_ID] === null) {
                    return null;
                }
            }
        }

        return $facility;
    }

    /**
     * Generate documentation of how metadata is parsed.
     * @return string human readable instructions
     */
    public function metadataToFacilityDoc()
    {
        $fields = <<<HEREDOC
serviceName.attrName=urn:perun:facility:attribute-def:def:serviceName
serviceName.position=1
serviceName.lang.name.en=Name
serviceName.lang.name.cs=Jméno
serviceName.lang.desc.en=Name of the service
serviceName.lang.desc.cs=Jméno služby
serviceName.allowedKeys=en,cs
serviceName.isDisplayed=True
serviceName.isEditable=True
serviceName.isRequired=True
serviceDescription.attrName=urn:perun:facility:attribute-def:def:serviceDescription
serviceDescription.position=2
serviceDescription.lang.name.en=Description
serviceDescription.lang.name.cs=Popis
serviceDescription.lang.desc.en=Short description of the service using plain language understandable for common end users (max 255 characters)
serviceDescription.lang.desc.cs=Stručný popis služby (max. 255 znaků)
serviceDescription.allowedKeys=en,cs
serviceDescription.isDisplayed=True
serviceDescription.isEditable=True
serviceDescription.isRequired=False
informationURL.attrName=urn:perun:facility:attribute-def:def:spInformationURL
informationURL.position=3
informationURL.lang.name.en=Information URL
informationURL.lang.name.cs=Informace o službě
informationURL.lang.desc.en=Link where user can find information about service or organization
informationURL.lang.desc.cs=Odkaz na informace o službě. Může také obsahovat informace o organizaci provozující službu
informationURL.allowedKeys=en,cs
informationURL.isDisplayed=True
informationURL.isEditable=True
informationURL.isRequired=False
loginURL.attrName=urn:perun:facility:attribute-def:def:loginURL
loginURL.position=4
loginURL.lang.name.en=Login URL
loginURL.lang.name.cs=URL přihlašovací stránky
loginURL.lang.desc.en=Link where users can access the service
loginURL.lang.desc.cs=Odkaz pro přístup ke službě
loginURL.isDisplayed=True
loginURL.isEditable=True
loginURL.isRequired=False
RaS.attrName=urn:perun:facility:attribute-def:def:RaS
RaS.position=5
RaS.lang.name.en=Research and Scholarship
RaS.lang.name.cs=Služba pochází z oblasti výzkumu a vzdělávaní
RaS.lang.desc.en=Service comes from the research and scholarship field
RaS.lang.desc.cs=Zaškrtněte, pokud je služba z oblasti výzkumu a vzdělávaní
RaS.isDisplayed=True
RaS.isEditable=True
RaS.isRequired=False
privacyPolicyURL.attrName=urn:perun:facility:attribute-def:def:privacyPolicyURL
privacyPolicyURL.position=6
privacyPolicyURL.lang.name.en=Privacy policy URL
privacyPolicyURL.lang.name.cs=Dokument o ochraně osobních údajů
privacyPolicyURL.lang.desc.en=Link to the privacy policy document of the organization or service
privacyPolicyURL.lang.desc.cs=Odkaz na dokument o ochraně osobních údajů
privacyPolicyURL.isDisplayed=True
privacyPolicyURL.isEditable=True
privacyPolicyURL.isRequired=False
spAdminContact.attrName=urn:perun:facility:attribute-def:def:spAdminContact
spAdminContact.position=7
spAdminContact.lang.name.en=Administrative contacts
spAdminContact.lang.name.cs=Administrativní kontakty
spAdminContact.lang.desc.en=Email of the persons responsible for the service
spAdminContact.lang.desc.cs=Emaily na osoby zodpovědné za provoz služby
spAdminContact.regex=^[A-Z0-9a-z._%+-]+@[a-z0-9.-]+\.[a-z]{2,64}$
spAdminContact.isDisplayed=True
spAdminContact.isEditable=True
spAdminContact.isRequired=False
spSupportContact.attrName=urn:perun:facility:attribute-def:def:spSupportContact
spSupportContact.position=8
spSupportContact.lang.name.en=Support contacts
spSupportContact.lang.name.cs=Kontakt na uživatelskou podporu
spSupportContact.lang.desc.en=Email to the support
spSupportContact.lang.desc.cs=Kontakt na uživatelskou podporu
spSupportContact.regex=^[A-Z0-9a-z._%+-]+@[a-z0-9.-]+\.[a-z]{2,64}$
spSupportContact.isDisplayed=True
spSupportContact.isEditable=True
spSupportContact.isRequired=False
organizationName.attrName=urn:perun:facility:attribute-def:def:organizationName
organizationName.position=1
organizationName.lang.name.en=Organization name
organizationName.lang.name.cs=Jméno organizace
organizationName.lang.desc.en=Name of the organization responsible for the service
organizationName.lang.desc.cs=Jméno organizace zodpovědné za službu
organizationName.allowedKeys=en,cs
organizationName.isDisplayed=True
organizationName.isEditable=True
organizationName.isRequired=False
organizationURL.attrName=urn:perun:facility:attribute-def:def:organizationURL
organizationURL.position=2
organizationURL.lang.name.en=Organization URL
organizationURL.lang.name.cs=URL organizace
organizationURL.lang.desc.en=URL with information about organization providing the service
organizationURL.lang.desc.cs=URL kde mohou být nalezeny informace o poskytovateli služby
organizationURL.isDisplayed=True
organizationURL.isEditable=True
organizationURL.isRequired=False
CoCo.attrName=urn:perun:facility:attribute-def:def:CoCo
CoCo.position=3
CoCo.lang.name.en=Organization commits to the GEANT Code of Conduct
CoCo.lang.name.cs=Organizace přizpívá do GEANT Code of Conduct
CoCo.lang.desc.en=http://www.geant.net/uri/dataprotection-code-of-conduct/v1
CoCo.lang.desc.cs=Zaškrtněte, pokud organizace přizpíva do GEANT Data protection Code of Conduct for Service Providers within EU/EEA
CoCo.isDisplayed=True
CoCo.isEditable=True
CoCo.isRequired=True
entityID.attrName=urn:perun:facility:attribute-def:def:entityID
entityID.position=1
entityID.lang.name.en=Entity ID
entityID.lang.name.cs=Entity ID
entityID.lang.desc.en=Entity ID in service SAML metadata
entityID.lang.desc.cs=Entity ID ze SAML metadat služby
entityID.isDisplayed=True
entityID.isEditable=True
entityID.isRequired=True
assertionConsumerService.attrName=urn:perun:facility:attribute-def:def:assertionConsumerServices
assertionConsumerService.position=2
assertionConsumerService.lang.name.en=Assertion consumer service
assertionConsumerService.lang.name.cs=Assertion consumer service
assertionConsumerService.lang.desc.en=Assertion consumer service in service SAML metadata
assertionConsumerService.lang.desc.cs=Assertion consumer service endpoint služby
assertionConsumerService.allowedKeys=HTTP-POST,HTTP-POST-SimpleSign,HTTP-Artifact,PAOS
assertionConsumerService.isDisplayed=True
assertionConsumerService.isEditable=True
assertionConsumerService.isRequired=False
singleLogoutService.attrName=urn:perun:facility:attribute-def:def:singleLogoutServices
singleLogoutService.position=3
singleLogoutService.lang.name.en=Single logout service
singleLogoutService.lang.name.cs=Single logout service
singleLogoutService.lang.desc.en=Single logout service in service SAML metadata
singleLogoutService.lang.desc.cs=Single logout service endpointy služby
singleLogoutService.allowedKeys=HTTP-Redirect,HTTP-POST,HTTP-Artifact,SOAP
singleLogoutService.isDisplayed=True
singleLogoutService.isEditable=True
singleLogoutService.isRequired=False
requiredAttributes.attrName=urn:perun:facility:attribute-def:def:requiredAttributes
requiredAttributes.position=4
requiredAttributes.lang.name.en=Attributes
requiredAttributes.lang.name.cs=Atributy
requiredAttributes.lang.desc.en=Select attributes which will be provided for the service
requiredAttributes.lang.desc.cs=Vyberte atributy se seznamu, které mají být službě poskytnuty
requiredAttributes.allowedValues=mail,tcsMail,tcsUnstructuredName,tcsCommonNameASCII,tcsSchacHomeOrg,eduPersonPrincipalName,eduPersonUniqueId,eduroamUID,eduPersonTargetedID,eduPersonTargetedID.old,eduPersonAffiliation,ssd,eduPersonScopedAffiliation,sn,cn,displayName,nameWithDegree,givenName,ou,o,mefanet,employee-type,academic,eduPersonEntitlement,eduPersonEntitlementMace,transientId,persistentId,voPersonExternalAffiliation,forwardedScopedAffiliation,schacHomeOrganization,bonaFideStatus,sourceIdPName,sourceIdPEntityID
requiredAttributes.isDisplayed=True
requiredAttributes.isEditable=True
requiredAttributes.isRequired=True
signingCert.attrName=urn:perun:facility:attribute-def:def:signingCert
signingCert.position=5
signingCert.lang.name.en=Signing certificate
signingCert.lang.name.cs=Podepisovací certifikát
signingCert.lang.desc.en=Signing certificate for your service from SAML metadata
signingCert.lang.desc.cs=Podepisovací certifikát služby
signingCert.isDisplayed=True
signingCert.isEditable=True
signingCert.isRequired=False
encryptionCert.attrName=urn:perun:facility:attribute-def:def:encryptionCert
encryptionCert.position=6
encryptionCert.lang.name.en=Encryption certificate
encryptionCert.lang.name.cs=Šifrovací certifikát
encryptionCert.lang.desc.en=Encryption certificate for your service from SAML metadata
encryptionCert.lang.desc.cs=Šifrovací certifikát služby
encryptionCert.isDisplayed=True
encryptionCert.isEditable=True
encryptionCert.isRequired=False
metadataURL.attrName=urn:perun:facility:attribute-def:def:metadataURL
metadataURL.position=7
metadataURL.lang.name.en=Metadata URL
metadataURL.lang.name.cs=URL metadat
metadataURL.lang.desc.en=URL where metadata can be found
metadataURL.lang.desc.cs=Odkaz vedoucí na metadata služby
metadataURL.isDisplayed=True
metadataURL.isEditable=True
metadataURL.isRequired=False
nameIDFormat.attrName=urn:perun:facility:attribute-def:def:nameIDFormat
nameIDFormat.position=8
nameIDFormat.lang.name.en=NameID format
nameIDFormat.lang.name.cs=NameID formát
nameIDFormat.lang.desc.en=NameID format
nameIDFormat.lang.desc.cs=NameID formát
nameIDFormat.isDisplayed=True
nameIDFormat.isEditable=True
nameIDFormat.isRequired=False
relayState.attrName=urn:perun:facility:attribute-def:def:relayState
relayState.position=9
relayState.lang.name.en=Relay state
relayState.lang.name.cs=Relay state
relayState.lang.desc.en=Relay state
relayState.lang.desc.cs=Relay state
relayState.isDisplayed=True
relayState.isEditable=True
relayState.isRequired=False
HEREDOC;
        $labels = [];
        $lastName = null;
        foreach (explode("\n", $fields) as $item) {
            preg_match('~\w+\.([\w\.]+)=(.*)~', $item, $matches);
            switch ($matches[1]) {
                case 'attrName':
                    $lastName = $matches[2];
                    break;
                case 'lang.name.en':
                    $labels[$lastName] = $matches[2];
                    break;
            }
        }

        $attributes = array_merge($this->addArrayAttributesDoc(), $this->addXmlAttributesDoc());
        foreach ($this->transformers as $transformer) {
            $attrs = array_intersect_key($attributes, array_flip($transformer['attributes']));
            $attributes = array_merge($attributes, $transformer['instance']->getDescription($attrs));
        }
        return '<table>' . implode(PHP_EOL, array_map(function ($attribute, $description) use ($labels) {
            return '<tr><td>'
                . $labels[array_search($attribute, $this->perunAttributes, true)]
                . '</td><td>'
                . preg_replace('~\((\*?\w+\*?)\)~', '$1', $description)
                . '</td></tr>';
        }, array_keys($attributes), $attributes));
    }

    /**
     * Load XML metadata file and get SPs.
     * @param string $filename
     * @return array metadata
     * @see getFacilitiesFrom()
     */
    public function getFacilitiesFromXml(string $filename)
    {
        return $this->getFacilitiesFrom(['type' => 'xml', 'file' => $filename]);
    }

    /**
     * Load flatfile metadata and get SPs.
     * @param string $directory
     * @return array metadata
     * @see getFacilitiesFrom()
     */
    public function getFacilitiesFromFlatfile(string $directory = null)
    {
        $config = ['type' => 'flatfile'];
        if ($directory !== null) {
            $config['directory'] = $directory;
        }
        return $this->getFacilitiesFrom($config);
    }

    /**
     * Load metadata and get SPs. See MetaDataStorageSource and subclasses for details.
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
                    if (!is_array($value) && substr($attribute['type'], -4) === 'List') {
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

    private function addArrayAttributesDoc()
    {
        $attributes = [];
        foreach ($this->flatfileAttributes as $perunAttribute => $metadataAttribute) {
            $attributes[$perunAttribute] = '*' . $metadataAttribute . '*';
        }
        return $attributes;
    }

    private function addXmlAttributes($metadata, &$facility)
    {
        $xml = base64_decode($metadata['entityDescriptor'], true);
        $xml = new \SimpleXMLElement($xml);
        foreach ($this->xmlAttributes as $perunAttribute => $xpath) {
        	if (is_string($xpath)) {
        		$result = $xml->xpath($xpath);
        		$result = ($result !== false && count($result) > 0) ? $result[0] : false;
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
	        				return $i !== false && count($i) > 0 ? ((string)$i[0]) : false;
	        			}, $result);
	        			if (in_array(false, $indexes, true) || count($indexes) !== count($result)) {
	        				throw new \Exception('Did not find corresponding number of keys using xpath ' . $index);
	        			}
	        			$result = array_combine(array_map('strval', $indexes), array_map('strval', $result)); // TODO: multiple keys same
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

    private function addXmlAttributesDoc()
    {
        $attributes = [];
        foreach ($this->xmlAttributes as $perunAttribute => $xpath) {
            $attributes[$perunAttribute] = sprintf(
                'XPATH(%s)',
                preg_replace('~\*\[local-name\(\) = "(.*?)"\]~', '$1', is_array($xpath) ? $xpath[0] : $xpath)
            );
        }
        return $attributes;
    }

    private function getAttributesDefinition(array $attrNames)
    {
        return array_values(
            array_filter(
                $this->adapter->getAttributesDefinition(),
                function ($attr) use ($attrNames) {
                    $perunName = $attr['namespace'] . self::NAMESPACE_SEPARATOR . $attr['friendlyName'];
                    return in_array($perunName, $attrNames, true);
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
        	'SP_MU_' . 
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
    private static function escapeFacilityName($str) {
    	return preg_replace('~[^-_\.a-zA-Z0-9]~', '_', $str);
    }

    /**
     * @return string
     */
    private static function stringOrEnglishOrAny($strOrEn) {
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
        $facility = ['facility' => [
            'name' => self::generateFacilityName($info),
            'description' => self::stringOrEnglishOrAny(
            	$info[self::SERVICE_DESCRIPTION] ?? $info[self::ORGANIZATION_DESCRIPTION] ?? ''
            ),
        ]];
        return $this->adapter->createFacility($facility);
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
}
