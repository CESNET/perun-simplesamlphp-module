<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\EntitlementUtils;

/**
 * Class PerunEntitlementExtended
 *
 * This filter joins extended version of eduPersonEntitlement, forwardedEduPersonEntitlement, resource capabilities and
 * facility capabilities
 *
 * @author Dominik Baránek <baranek@ics.muni.cz>
 */
class PerunEntitlementExtended extends ProcessingFilter
{
    public const CONFIG_FILE_NAME = 'module_perun.php';

    public const OUTPUT_ATTR_NAME = 'outputAttrName';

    public const RELEASE_FORWARDED_ENTITLEMENT = 'releaseForwardedEntitlement';

    public const FORWARDED_EDU_PERSON_ENTITLEMENT = 'forwardedEduPersonEntitlement';

    public const ENTITLEMENTPREFIX_ATTR = 'entitlementPrefix';

    public const ENTITLEMENTAUTHORITY_ATTR = 'entitlementAuthority';

    public const GROUPNAMEAARC_ATTR = 'groupNameAARC';

    public const INTERFACE_PROPNAME = 'interface';

    public const ENTITY_ID = 'entityID';

    private $outputAttrName;

    private $releaseForwardedEntitlement;

    private $forwardedEduPersonEntitlement;

    private $entitlementPrefix;

    private $entitlementAuthority;

    private $groupNameAARC;

    private $adapter;

    private $entityId;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $modulePerunConfiguration = Configuration::getConfig(self::CONFIG_FILE_NAME);
        assert('is_array($config)');

        $configuration = Configuration::loadFromArray($config);

        $this->outputAttrName = $configuration->getString(self::OUTPUT_ATTR_NAME, 'eduPersonEntitlementExtended');
        $this->releaseForwardedEntitlement = $configuration->getBoolean(self::RELEASE_FORWARDED_ENTITLEMENT, true);
        $this->forwardedEduPersonEntitlement = $configuration->getString(
            self::FORWARDED_EDU_PERSON_ENTITLEMENT,
            $this->releaseForwardedEntitlement ? Configuration::REQUIRED_OPTION : ''
        );

        $this->groupNameAARC = $modulePerunConfiguration->getBoolean(self::GROUPNAMEAARC_ATTR, false);
        $this->entitlementPrefix = $modulePerunConfiguration->getString(
            self::ENTITLEMENTPREFIX_ATTR,
            $this->groupNameAARC ? Configuration::REQUIRED_OPTION : ''
        );
        $this->entitlementAuthority = $modulePerunConfiguration->getString(
            self::ENTITLEMENTAUTHORITY_ATTR,
            $this->groupNameAARC ? Configuration::REQUIRED_OPTION : ''
        );

        $this->entityId = $configuration->getValue(self::ENTITY_ID, null);

        $interface = $configuration->getValueValidate(
            self::INTERFACE_PROPNAME,
            [Adapter::RPC, Adapter::LDAP],
            Adapter::RPC
        );
        $this->adapter = Adapter::getInstance($interface);
    }

    public function process(&$request)
    {
        $eduPersonEntitlementExtended = [];
        $capabilities = [];
        $forwardedEduPersonEntitlement = [];

        if ($this->entityId === null) {
            $this->entityId = EntitlementUtils::getSpEntityId($request);
        } elseif (is_callable($this->entityId)) {
            $this->entityId = call_user_func($this->entityId, $request);
        } elseif (! is_string($this->entityId)) {
            throw new Exception(
                'perun:PerunEntitlement: invalid configuration option entityID. ' .
                'It must be a string or a callable.'
            );
        }

        if (isset($request['perun']['groups'])) {
            $eduPersonEntitlementExtended = $this->getEduPersonEntitlementExtended($request);

            $capabilities = EntitlementUtils::getCapabilities(
                $request,
                $this->adapter,
                $this->entitlementPrefix,
                $this->entitlementAuthority,
                $this->entityId
            );
        } else {
            Logger::debug(
                'perun:PerunEntitlementExtended: There are no user groups assigned to facility.' .
                '=> Skipping getEduPersonEntitlementExtended and getCapabilities'
            );
        }

        if ($this->releaseForwardedEntitlement) {
            $forwardedEduPersonEntitlement = EntitlementUtils::getForwardedEduPersonEntitlement(
                $request,
                $this->adapter,
                $this->forwardedEduPersonEntitlement
            );
        }

        $request['Attributes'][$this->outputAttrName] = array_unique(array_merge(
            $eduPersonEntitlementExtended,
            $forwardedEduPersonEntitlement,
            $capabilities
        ));
    }

    private function getEduPersonEntitlementExtended(&$request)
    {
        $eduPersonEntitlementExtended = [];

        $groups = $request['perun']['groups'];
        foreach ($groups as $group) {
            $entitlement = EntitlementUtils::groupEntitlementWrapper(
                $group->getUuid(),
                $this->entitlementPrefix,
                $this->entitlementAuthority
            );

            array_push($eduPersonEntitlementExtended, $entitlement);

            $groupName = $group->getUniqueName();
            $groupName = preg_replace('/^(\w*)\:members$/', '$1', $groupName);

            $entitlementWithAttributes = EntitlementUtils::groupEntitlementWithAttributesWrapper(
                $group->getUuid(),
                $groupName,
                $this->entitlementPrefix,
                $this->entitlementAuthority
            );

            array_push($eduPersonEntitlementExtended, $entitlementWithAttributes);
        }

        natsort($eduPersonEntitlementExtended);
        return $eduPersonEntitlementExtended;
    }
}
