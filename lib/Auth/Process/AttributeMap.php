<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\EntitlementUtils;

class AttributeMap extends \SimpleSAML\Auth\ProcessingFilter
{
    public const MAP_ATTR_NAME = 'attrMapAttr';
    public const KEEP_SOURCE_ATTRIBUTES = 'keepSourceAttributes';
    public const ENTITY_ID = 'entityid';
    public const INTERFACE_PROPNAME = 'interface';

    public const CLASS_PREFIX = 'perun:AttributeMap: ';
    public const ATTRIBUTES = 'Attributes';

    private $mapAttrName;
    private $keepSourceAttributes;
    private $entityId;
    private $adapter;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);

        $this->mapAttrName = $config->getString(self::MAP_ATTR_NAME);
        $this->keepSourceAttributes = $config->getBoolean(self::KEEP_SOURCE_ATTRIBUTES, false);
        $this->entityId = $config->getValue(self::ENTITY_ID);

        $interface = $config->getValueValidate(
            self::INTERFACE_PROPNAME,
            [Adapter::RPC, Adapter::LDAP],
            Adapter::RPC
        );

        $this->adapter = Adapter::getInstance($interface);
    }

    public function process(&$request)
    {
        if (null === $this->entityId) {
            $this->entityId = EntitlementUtils::getSpEntityId($request);
        } elseif (is_callable($this->entityId)) {
            $this->entityId = call_user_func($this->entityId, $request);
        } elseif (!is_string($this->entityId)) {
            throw new Exception(self::CLASS_PREFIX . 'entityid must be a string or a callable');
        }

        $facility = $this->adapter->getFacilityByEntityId($this->entityId);

        if (null === $facility) {
            Logger::info(
                self::CLASS_PREFIX . 'Facility with entityid ' . $this->entityId . ' not found. Skipping the filter'
            );

            return;
        }

        $attrMap = $this->adapter->getFacilityAttributesValues(
            $facility,
            [$this->mapAttrName]
        )[self::MAP_ATTR_NAME] ?? [];
        $requestAttributes = &$request[self::ATTRIBUTES];

        $mappedSourceAttributes = [];

        foreach ($attrMap as $targetAttribute => $sourceAttribute) {
            if (isset($requestAttributes[$sourceAttribute])) {
                $requestAttributes[$targetAttribute] = $requestAttributes[$sourceAttribute];

                if (!$this->keepSourceAttributes && !in_array($sourceAttribute, $mappedSourceAttributes, true)) {
                    array_push($mappedSourceAttributes, $sourceAttribute);
                }
            }
        }

        if (!$this->keepSourceAttributes) {
            $requestAttributes = array_diff_key($requestAttributes, array_flip($mappedSourceAttributes));
        }
    }
}
