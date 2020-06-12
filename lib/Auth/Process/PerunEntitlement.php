<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Configuration;
use \SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Logger;

/**
 * Class PerunEntitlement
 *
 * This filter joins eduPersonEntitlement, forwardedEduPersonEntitlement, resource capabilities
 * and facility capabilities
 *
 * @author Dominik Baránek <baranek@ics.muni.cz>
 * @author Pavel Vyskočil <Pavel.Vyskocil@cesnet.cz>
 */
class PerunEntitlement extends ProcessingFilter
{
    const CONFIG_FILE_NAME = 'module_perun.php';
    const EDU_PERSON_ENTITLEMENT = 'eduPersonEntitlement';
    const RELEASE_FORWARDED_ENTITLEMENT = 'releaseForwardedEntitlement';
    const FORWARDED_EDU_PERSON_ENTITLEMENT = 'forwardedEduPersonEntitlement';
    const ENTITLEMENTPREFIX_ATTR = 'entitlementPrefix';
    const ENTITLEMENTAUTHORITY_ATTR = 'entitlementAuthority';
    const GROUPNAMEAARC_ATTR = 'groupNameAARC';
    const INTERFACE_PROPNAME = 'interface';

    private $eduPersonEntitlement;
    private $releaseForwardedEntitlement;
    private $forwardedEduPersonEntitlement;
    private $entitlementPrefix;
    private $entitlementAuthority;
    private $groupNameAARC;
    private $adapter;

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

        if (isset($request['perun']['groups'])) {
            $eduPersonEntitlement = $this->getEduPersonEntitlement($request);
            $capabilities = $this->getCapabilities($request);
        } else {
            Logger::debug(
                'perun:PerunEntitlement: There are no user groups assign to facility.' .
                '=> Skipping getEduPersonEntitlement and getResourceCapabilities'
            );
        }

        if ($this->releaseForwardedEntitlement) {
            $forwardedEduPersonEntitlement = $this->getForwardedEduPersonEntitlement($request);
        }

        $request['Attributes'][$this->eduPersonEntitlement] = array_unique(array_merge(
            $eduPersonEntitlement,
            $forwardedEduPersonEntitlement,
            $capabilities
        ));
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

    private function getForwardedEduPersonEntitlement(&$request)
    {
        $forwardedEduPersonEntitlement = [];

        if (!isset($request['perun']['user'])) {
            Logger::debug(
                'perun:PerunEntitlement: Object Perun User is not specified.' .
                '=> Skipping getting forwardedEntitlement.'
            );
            return $forwardedEduPersonEntitlement;
        }

        $user = $request['perun']['user'];

        try {
            $forwardedEduPersonEntitlementMap = $this->adapter->getUserAttributes(
                $user,
                [$this->forwardedEduPersonEntitlement]
            );
        } catch (Exception $exception) {
            Logger::error(
                'perun:PerunEntitlement: Exception ' . $exception->getMessage() .
                ' was thrown in method \'getForwardedEduPersonEntitlement\'.'
            );
        }

        if (!empty($forwardedEduPersonEntitlementMap)) {
            $forwardedEduPersonEntitlement = array_values($forwardedEduPersonEntitlementMap)[0];
        }

        return $forwardedEduPersonEntitlement;
    }

    private function getCapabilities(&$request)
    {
        $resourceCapabilities = [];
        $facilityCapabilities = [];
        $capabilitiesResult = [];

        $spEntityId = $this->getSpEntityId($request);
        try {
            $resourceCapabilities = $this->adapter->getResourceCapabilities($spEntityId, $request['perun']['groups']);
            $facilityCapabilities = $this->adapter->getFacilityCapabilities($spEntityId);
        } catch (Exception $exception) {
            Logger::error(
                'perun:PerunEntitlement: Exception ' . $exception->getMessage() .
                ' was thrown in method \'getCapabilities\'.'
            );
        }

        $capabilities = array_unique(array_merge($resourceCapabilities, $facilityCapabilities));

        foreach ($capabilities as $capability) {
            $wrappedCapability = $this->capabilitiesWrapper($capability);
            array_push($capabilitiesResult, $wrappedCapability);
        }

        return $capabilitiesResult;
    }

    private function groupNameWrapper($groupName)
    {
        return $this->entitlementPrefix . 'group:' . implode(':', $this->encodeName($groupName)) .
               '#' . $this->entitlementAuthority;
    }

    private function capabilitiesWrapper($capabilities)
    {
        return $this->entitlementPrefix . implode(':', $this->encodeName($capabilities)) .
               '#' . $this->entitlementAuthority;
    }

    /**
     * This method translates given name of group based on associative array 'groupMapping' in SP metadata.
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
                ' for SP ' . $request['SPMetadata']['entityid']
            );
            return $request['SPMetadata']['groupMapping'][$groupName];
        } elseif (isset($request['SPMetadata'][self::ENTITLEMENTPREFIX_ATTR])) {
            Logger::debug(
                'EntitlementPrefix overridden by a SP ' . $request['SPMetadata']['entityid'] .
                ' to ' . $request['SPMetadata'][self::ENTITLEMENTPREFIX_ATTR]
            );
            return $request['SPMetadata'][self::ENTITLEMENTPREFIX_ATTR] . $groupName;
        } else {
            # No mapping defined, so just put groupNamePrefix in front of the group
            Logger::debug(
                'No mapping found for group ' . $groupName . ' for SP ' . $request['SPMetadata']['entityid']
            );
            return $this->entitlementPrefix . 'group:' . $groupName;
        }
    }

    private function encodeName($name)
    {
        $charsToSkip = [
            '!' => '%21',
            '$' => '%24',
            '\'' => '%27',
            '(' => '%28',
            ')' => '%29',
            '*' => '%2A',
            ',' => '%2C',
            ';' => '%3B',
            '&' => '%26',
            '=' => '%3D',
            '@' => '%40',
            ':' => '%3A',
            '+' => '%2B'
        ];

        $name = array_map('rawurlencode', explode(':', $name));

        foreach ($charsToSkip as $key => $value) {
            $name = str_replace($value, $key, $name);
        }

        return $name;
    }

    private function getSpEntityId(&$request)
    {
        if (isset($request['SPMetadata']['entityid'])) {
            return $request['SPMetadata']['entityid'];
        } else {
            throw new Exception('perun:PerunEntitlement: Cannot find entityID of remote SP. ' .
                'hint: Do you have this filter in IdP context?');
        }
    }
}
