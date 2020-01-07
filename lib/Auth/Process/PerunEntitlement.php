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
 * This filter joins eduPersonEntitlement, forwardedEduPersonEntitlement and resource capabilities
 *
 * @author Dominik Baránek <baranek@ics.muni.cz>
 */
class PerunEntitlement extends ProcessingFilter
{
    const CONFIG_FILE_NAME = 'module_perun.php';
    const EDU_PERSON_ENTITLEMENT = 'eduPersonEntitlement';
    const FORWARDED_EDU_PERSON_ENTITLEMENT = 'forwardedEduPersonEntitlement';
    const ENTITLEMENTPREFIX_ATTR = 'entitlementPrefix';
    const ENTITLEMENTAUTHORITY_ATTR = 'entitlementAuthority';
    const GROUPNAMEAARC_ATTR = 'groupNameAARC';
    const INTERFACE_PROPNAME = 'interface';

    private $eduPersonEntitlement;
    private $forwardedEduPersonEntitlement;
    private $entitlementPrefix;
    private $entitlementAuthority;
    private $groupNameAARC;
    private $interface;
    private $adapter;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        assert('is_array($config)');

        if (!isset($config[self::EDU_PERSON_ENTITLEMENT])) {
            throw new Exception(
                'perun:PerunEntitlement: missing mandatory configuration option ' .
                self::EDU_PERSON_ENTITLEMENT . '.'
            );
        }
        $this->eduPersonEntitlement = $config[self::EDU_PERSON_ENTITLEMENT];

        if (!isset($config[self::FORWARDED_EDU_PERSON_ENTITLEMENT])) {
            throw new Exception(
                'perun:PerunEntitlement: missing mandatory configuration option ' .
                self::FORWARDED_EDU_PERSON_ENTITLEMENT . '.'
            );
        }
        $this->forwardedEduPersonEntitlement = $config[self::FORWARDED_EDU_PERSON_ENTITLEMENT];

        $this->entitlementPrefix = $conf->getString(self::ENTITLEMENTPREFIX_ATTR, '');
        $this->entitlementAuthority = $conf->getString(self::ENTITLEMENTAUTHORITY_ATTR, '');
        $this->groupNameAARC = $conf->getBoolean(self::GROUPNAMEAARC_ATTR, false);

        if ($this->groupNameAARC && (empty($this->entitlementAuthority) || empty($this->entitlementPrefix))) {
            throw new Exception(
                'perun:PerunEntitlement: \'groupNameAARC\' has been set, \'entitlementAuthority\' ' .
                'and \'entitlementPrefix\' options must be set as well'
            );
        }

        if (!isset($config[self::INTERFACE_PROPNAME])) {
            $config[self::INTERFACE_PROPNAME] = Adapter::RPC;
        }

        $this->interface = (string)$config[self::INTERFACE_PROPNAME];
        $this->adapter = Adapter::getInstance($this->interface);
    }

    public function process(&$request)
    {
        $eduPersonEntitlement = $this->getEduPersonEntitlement($request);
        $forwardedEduPersonEntitlement = $this->getForwardedEduPersonEntitlement($request);
        $resourceCapabilities = $this->getResourceCapabilities($request);

        $request['Attributes'][$this->eduPersonEntitlement] = array_unique(array_merge(
            $eduPersonEntitlement,
            $forwardedEduPersonEntitlement,
            $resourceCapabilities
        ));
    }

    private function getEduPersonEntitlement(&$request)
    {
        if (isset($request['perun']['groups'])) {
            /** allow IDE hint whisperer
             * @var model\Group[] $groups
             */
            $groups = $request['perun']['groups'];
        } else {
            throw new Exception(
                'perun:PerunEntitlement: ' .
                'missing mandatory field \'perun.groups\' in request.' .
                'Hint: Did you configure PerunIdentity filter before this filter?'
            );
        }

        $eduPersonEntitlement = [];
        foreach ($groups as $group) {
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

                $groupName = $this->groupNameWrapper($group->getUniqueName());
            } else {
                $groupName = $this->mapGroupName($request, $group->getUniqueName());
            }
            array_push($eduPersonEntitlement, $groupName);
        }

        return $eduPersonEntitlement;
    }

    private function getForwardedEduPersonEntitlement(&$request)
    {
        $forwardedEduPersonEntitlement = [];
        $user = $request['perun']['user'];
        $forwardedEduPersonEntitlementMap = $this->adapter->getUserAttributes(
            $user,
            [$this->forwardedEduPersonEntitlement]
        );

        if (!empty($forwardedEduPersonEntitlementMap)) {
            $forwardedEduPersonEntitlement = array_values($forwardedEduPersonEntitlementMap)[0];
        }

        return $forwardedEduPersonEntitlement;
    }

    private function getResourceCapabilities(&$request)
    {
        if (isset($request['SPMetadata']['entityid'])) {
            $spEntityId = $request['SPMetadata']['entityid'];
        } else {
            throw new Exception('perun:PerunEntitlement: Cannot find entityID of remote SP. ' .
                'hint: Do you have this filter in IdP context?');
        }

        $capabilities = $this->adapter->getResourceCapabilities($spEntityId, $request['perun']['groups']);
        $capabilitiesResult = [];

        foreach ($capabilities as $capability) {
            $resourceCapability = $this->capabilitiesWrapper($capability);
            array_push($capabilitiesResult, $resourceCapability);
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
                'Mapping $groupName to ' . $request['SPMetadata']['groupMapping'][$groupName] .
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
                'No mapping found for group $groupName for SP ' . $request['SPMetadata']['entityid']
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
}
