<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SAML2\XML\saml\NameID;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\PerunConstants;

/**
 * Adds qualifiers to the NameID object.
 */
class QualifyNameID extends ProcessingFilter
{
    public const STAGE = 'perun:QualifyNameID';
    public const DEBUG_PREFIX = self::STAGE . ' - ';

    public const NAME_ID_CLASS = 'SAML2\XML\saml\NameID';

    public const NAME_ID_ATTRIBUTE = 'name_id_attribute';
    public const NAME_QUALIFIER = 'name_qualifier';
    public const NAME_QUALIFIER_ATTRIBUTE = 'name_qualifier_attribute';
    public const SP_NAME_QUALIFIER = 'sp_name_qualifier';
    public const SP_NAME_QUALIFIER_ATTRIBUTE = 'sp_name_qualifier_attribute';

    private $targetedIdAttribute;
    private $nameQualifier;
    private $nameQualifierAttribute;
    private $spNameQualifier;
    private $spNameQualifierAttribute;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $filterConfig = Configuration::loadFromArray($config);

        $this->targetedIdAttribute = $filterConfig->getString(self::NAME_ID_ATTRIBUTE, null);
        if (empty($this->targetedIdAttribute)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'missing mandatory configuration for option \'' . self::NAME_ID_ATTRIBUTE . '\''
            );
        }

        $this->nameQualifier = $filterConfig->getString(self::NAME_QUALIFIER, null);
        $this->nameQualifierAttribute = $filterConfig->getString(self::NAME_QUALIFIER_ATTRIBUTE, null);
        if (empty($this->nameQualifier) && empty($this->nameQualifierAttribute)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'missing mandatory configuration for option \'' . self::NAME_QUALIFIER . '\' or \'' . self::NAME_QUALIFIER_ATTRIBUTE . '\', one must be configured.'
            );
        }

        $this->spNameQualifier = $filterConfig->getString(self::SP_NAME_QUALIFIER, null);
        $this->spNameQualifierAttribute = $filterConfig->getString(self::SP_NAME_QUALIFIER_ATTRIBUTE, null);
        if (empty($this->spNameQualifier) && empty($this->spNameQualifierAttribute)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'missing mandatory configuration for option \'' . self::SP_NAME_QUALIFIER . '\' or \'' . self::SP_NAME_QUALIFIER_ATTRIBUTE . '\', one must be configured.'
            );
        }
    }

    public function process(&$request)
    {
        assert(is_array($request));
        assert(!empty(PerunConstants::ATTRIBUTES));

        if (!empty($request[PerunConstants::ATTRIBUTES][$this->targetedIdAttribute])) {
            $attributeValue = &$request[PerunConstants::ATTRIBUTES][$this->targetedIdAttribute][0];
            if (self::NAME_ID_CLASS === get_class($attributeValue)) {
                $nameQualifier = $request[PerunConstants::ATTRIBUTES][$this->nameQualifierAttribute][0] ?? $this->nameQualifier;
                if (empty($nameQualifier)) {
                    throw new Exception(self::DEBUG_PREFIX . 'NameQualifier is not available');
                }
                $spNameQualifier = $request[PerunConstants::ATTRIBUTES][$this->spNameQualifierAttribute][0] ?? $this->spNameQualifier;
                if (empty($spNameQualifier)) {
                    throw new Exception(self::DEBUG_PREFIX . 'SPNameQualifier is not available');
                }
                $this->qualify($attributeValue, $nameQualifier, $spNameQualifier);
                Logger::debug(
                    self::DEBUG_PREFIX . 'Qualification done successfully for attribute \'' . $this->targetedIdAttribute
                    . '\' (SPNameQualifier: ' . $spNameQualifier . ', NameQualifier: ' . $nameQualifier . ').'
                );
            } else {
                Logger::debug(
                    self::DEBUG_PREFIX . 'Cannot qualify, class of the attribute \'' . $this->targetedIdAttribute
                    . '\' (' . get_class($attributeValue) . ') is not equal to ' . self::NAME_ID_CLASS . '.'
                );
            }
        } else {
            Logger::debug(
                self::DEBUG_PREFIX . 'Attribute \'' . $this->targetedIdAttribute . '\' not available, cannot qualify.'
            );
        }
    }

    private function qualify(NameID $attributeValue, string $nameQualifier, string $spNameQualifier)
    {
        $attributeValue->setNameQualifier($nameQualifier);
        $attributeValue->setSPNameQualifier($spNameQualifier);
    }
}
