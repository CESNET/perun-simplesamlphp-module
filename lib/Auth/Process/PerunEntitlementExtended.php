<?php


namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Configuration;
use \SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\EntitlementUtils;

/**
 * Class PerunEntitlementExtended
 *
 * This filter joins extended version of eduPersonEntitlement, forwardedEduPersonEntitlement, resource capabilities
 * and facility capabilities
 *
 * @author Dominik Baránek <baranek@ics.muni.cz>
 */
class PerunEntitlementExtended extends ProcessingFilter
{
    const CONFIG_FILE_NAME = 'module_perun.php';
    const OUTPUT_ATTR_NAME = 'outputAttrName';
    const RELEASE_FORWARDED_ENTITLEMENT = 'releaseForwardedEntitlement';
    const FORWARDED_EDU_PERSON_ENTITLEMENT = 'forwardedEduPersonEntitlement';
    const ENTITLEMENTPREFIX_ATTR = 'entitlementPrefix';
    const ENTITLEMENTAUTHORITY_ATTR = 'entitlementAuthority';
    const GROUPNAMEAARC_ATTR = 'groupNameAARC';
    const INTERFACE_PROPNAME = 'interface';
    const ENTITY_ID = 'entityID';

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

        $this->entityId = $modulePerunConfiguration->getString(self::ENTITY_ID, null);

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
