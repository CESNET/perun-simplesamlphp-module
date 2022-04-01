<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Error\MetadataNotFound;
use SimpleSAML\Logger;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\perun\PerunConstants;

/**
 * Generated attributes based on IdP metadata
 */
class GenerateIdPAttributes extends ProcessingFilter
{
    public const STAGE = 'perun:GenerateIdPAttributes';
    public const DEBUG_PREFIX = self::STAGE . ' - ';

    public const ATTRIBUTE_MAP = 'attribute_map';
    public const IDP_IDENTIFIER_ATTRIBUTE = 'idp_identifier_attribute';

    public const SAML_SP_IDP = 'saml:sp:IdP';
    public const SAML20_IDP_REMOTE = 'saml20-idp-remote';

    private $attributeMap;
    private $idpIdentifierAttribute;
    private $filterConfig;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $this->filterConfig = Configuration::loadFromArray($config);

        $this->attributeMap = $this->filterConfig->getArray(self::ATTRIBUTE_MAP, []);
        if (empty($this->attributeMap)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Invalid configuration: no map of attributes for generation ' . 'has been configured. Use option \'' . self::ATTRIBUTE_MAP . '\' to configure the map of keys in ' . 'IDP metadata to names of attributes to generate.'
            );
        }

        $this->idpIdentifierAttribute = $this->filterConfig->getString(self::IDP_IDENTIFIER_ATTRIBUTE, null);
        if (empty($this->idpIdentifierAttribute)) {
            Logger::debug(
                self::DEBUG_PREFIX . 'No name of attribute containing IDP identifier configured. '
                . 'Will use default entry in request object \'saml:sp:IdP\''
            );
        }
    }

    public function process(&$request)
    {
        assert(is_array($request));

        $sourceIdpMeta = null;
        $metadataHandler = MetaDataStorageHandler::getMetadataHandler();
        if (!empty($this->idpIdentifierAttribute)) {
            $idpIdentifier = $request[PerunConstants::ATTRIBUTES][$this->idpIdentifierAttribute][0] ?? null;
            if (!empty($idpIdentifier)) {
                try {
                    $sourceIdpMeta = $metadataHandler->getMetaData($idpIdentifier, self::SAML20_IDP_REMOTE);
                } catch (MetadataNotFound $ex) {
                    Logger::warning(self::DEBUG_PREFIX . 'Metadata for IDP \'' . $idpIdentifier . '\' not found.');
                }
            } else {
                Logger::debug(
                    self::DEBUG_PREFIX . 'Could not extract IDP identifier from the attributes. Did you '
                    . 'configure \'' . ExtractRequestAttribute::STAGE . '\' filter to be run before this one?'
                );
            }
        }

        if (empty($sourceIdpMeta)) {
            Logger::debug(self::DEBUG_PREFIX . 'Trying to use key \'' . self::SAML_SP_IDP . '\' instead.');
            $idpIdentifier = $request[self::SAML_SP_IDP] ?? null;
            Logger::debug(self::DEBUG_PREFIX . 'Using \'' . $idpIdentifier . '\' as the IDP identifier');
            if (!empty($idpIdentifier)) {
                try {
                    $sourceIdpMeta = $metadataHandler->getMetaData($idpIdentifier, self::SAML20_IDP_REMOTE);
                } catch (MetadataNotFound $exc) {
                    // this should never happen
                    Logger::warning(self::DEBUG_PREFIX . 'Metadata for IDP \'' . $idpIdentifier . '\' not found.');
                    throw new Exception(self::DEBUG_PREFIX . 'Could not find metadata for the authenticating IdP');
                }
            } else {
                throw new Exception(self::DEBUG_PREFIX . 'Could not find identifier of the authenticating IDP');
            }
        }

        foreach ($this->attributeMap as $sourceAttributeName => $destinationAttributeName) {
            $attributeNames = preg_split('/:/', $sourceAttributeName);
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
                $value = [$value];
            }

            if (!empty($value)) {
                $request[PerunConstants::ATTRIBUTES][$destinationAttributeName] = $value;
                Logger::debug(
                    self::DEBUG_PREFIX . 'Added attribute from metadata with key \'' . $sourceAttributeName
                    . '\' as attribute \'' . $destinationAttributeName . '\' with value \'' . implode(';', $value)
                );
            }
        }
    }
}
