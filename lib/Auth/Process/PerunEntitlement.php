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
 * Class PerunEntitlement
 *
 * This filter joins eduPersonEntitlement, forwardedEduPersonEntitlement, resource capabilities and facility
 * capabilities
 *
 * @author Dominik Baránek <baranek@ics.muni.cz>
 * @author Pavel Vyskočil <Pavel.Vyskocil@cesnet.cz>
 */
class PerunEntitlement extends ProcessingFilter
{
    public const CONFIG_FILE_NAME = 'module_perun.php';

    public const EDU_PERSON_ENTITLEMENT = 'eduPersonEntitlement';

    public const RELEASE_FORWARDED_ENTITLEMENT = 'releaseForwardedEntitlement';

    public const FORWARDED_EDU_PERSON_ENTITLEMENT = 'forwardedEduPersonEntitlement';

    public const ENTITLEMENTPREFIX_ATTR = 'entitlementPrefix';

    public const ENTITLEMENTAUTHORITY_ATTR = 'entitlementAuthority';

    public const GROUPNAMEAARC_ATTR = 'groupNameAARC';

    public const INTERFACE_PROPNAME = 'interface';

    public const ENTITY_ID = 'entityID';

    private $eduPersonEntitlement;

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

        $this->eduPersonEntitlement = $configuration->getString(self::EDU_PERSON_ENTITLEMENT);
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
        $eduPersonEntitlement = [];
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
            $eduPersonEntitlement = $this->getEduPersonEntitlement($request);
            $capabilities = EntitlementUtils::getCapabilities(
                $request,
                $this->adapter,
                $this->entitlementPrefix,
                $this->entitlementAuthority,
                $this->entityId
            );
        } else {
            Logger::debug(
                'perun:PerunEntitlement: There are no user groups assigned to facility.' .
                '=> Skipping getEduPersonEntitlement and getCapabilities'
            );
        }

        if ($this->releaseForwardedEntitlement) {
            $forwardedEduPersonEntitlement = EntitlementUtils::getForwardedEduPersonEntitlement(
                $request,
                $this->adapter,
                $this->forwardedEduPersonEntitlement
            );
        }

        $request['Attributes'][$this->eduPersonEntitlement] = array_unique(array_merge(
            $eduPersonEntitlement,
            $forwardedEduPersonEntitlement,
            $capabilities
        ));
    }

    /**
     * This method translates given name of group based on associative array 'groupMapping' in SP metadata.
     *
     * @param $request
     * @param string $groupName
     * @return string translated group name
     */
    protected function mapGroupName($request, $groupName)
    {
        if (
            isset($request['SPMetadata']['groupMapping']) &&
            isset($request['SPMetadata']['groupMapping'][$groupName])) {
            Logger::debug(
                'Mapping ' . $groupName . ' to ' . $request['SPMetadata']['groupMapping'][$groupName] .
                ' for SP ' . $this->entityId
            );
            return $request['SPMetadata']['groupMapping'][$groupName];
        } elseif (isset($request['SPMetadata'][self::ENTITLEMENTPREFIX_ATTR])) {
            Logger::debug(
                'EntitlementPrefix overridden by a SP ' . $this->entityId .
                ' to ' . $request['SPMetadata'][self::ENTITLEMENTPREFIX_ATTR]
            );
            return $request['SPMetadata'][self::ENTITLEMENTPREFIX_ATTR] . $groupName;
        }
        # No mapping defined, so just put groupNamePrefix in front of the group
        Logger::debug('No mapping found for group ' . $groupName . ' for SP ' . $this->entityId);
        return $this->entitlementPrefix . 'group:' . $groupName;
    }

    private function getEduPersonEntitlement(&$request)
    {
        $eduPersonEntitlement = [];

        $groups = $request['perun']['groups'];
        foreach ($groups as $group) {
            $groupName = $group->getUniqueName();
            $groupName = preg_replace('/^(\w*)\:members$/', '$1', $groupName);

            if (isset($request['SPMetadata']['groupNameAARC']) || $this->groupNameAARC) {
                # https://aarc-project.eu/wp-content/uploads/2017/11/AARC-JRA1.4A-201710.pdf
                # Group name is URL encoded by RFC 3986 (http://www.ietf.org/rfc/rfc3986.txt)
                # Example:
                # urn:geant:einfra.cesnet.cz:perun.cesnet.cz:group:einfra:<groupName>:<subGroupName>#perun.cesnet.cz
                if (empty($this->entitlementAuthority) || empty($this->entitlementPrefix)) {
                    throw new Exception(
                        'perun:PerunEntitlement: missing mandatory configuration options ' .
                        '\'groupNameAuthority\' or \'groupNamePrefix\'.'
                    );
                }
                $groupName = $this->groupNameWrapper($groupName);
            } else {
                $groupName = $this->mapGroupName($request, $groupName);
            }
            array_push($eduPersonEntitlement, $groupName);
        }
        natsort($eduPersonEntitlement);
        return $eduPersonEntitlement;
    }

    private function groupNameWrapper($groupName)
    {
        return $this->entitlementPrefix . 'group:' .
            implode(':', EntitlementUtils::encodeEntitlement($groupName)) .
            '#' . $this->entitlementAuthority;
    }
}
