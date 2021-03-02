<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\Module\perun\model\User;
use SimpleSAML\Module\perun\model\Group;
use SimpleSAML\Module\perun\model\Vo;
use SimpleSAML\Module\perun\model\Member;
use SimpleSAML\Module\perun\model\Facility;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun;

/**
 * Class AdapterLdap
 *
 * Configuration file should be placed in default config folder of SimpleSAMLphp.
 * Example of file is in config-template folder.
 *
 * Perun adapter which uses Perun LDAP interface
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class AdapterLdap extends Adapter
{
    const DEFAULT_CONFIG_FILE_NAME = 'module_perun.php';
    const LDAP_HOSTNAME = 'ldap.hostname';
    const LDAP_USER = 'ldap.username';
    const LDAP_PASSWORD = 'ldap.password';
    const LDAP_BASE = 'ldap.base';
    const LDAP_TLS = 'ldap.enable_tls';
    const PERUN_FACILITY_ID = 'perunFacilityId';
    const CN = 'cn';
    const DESCRIPTION = 'description';
    const CAPABILITIES = 'capabilities';
    const ASSIGNED_GROUP_ID = 'assignedGroupId';
    const TYPE_BOOL = 'bool';
    const TYPE_MAP = 'map';
    const INTERNAL_ATTR_NAME = 'internalAttrName';
    const TYPE = 'type';

    private $ldapBase;
    private $fallbackAdapter;

    protected $connector;

    public function __construct($configFileName = null)
    {
        if ($configFileName === null) {
            $configFileName = self::DEFAULT_CONFIG_FILE_NAME;
        }

        $conf = Configuration::getConfig($configFileName);

        $ldapHostname = $conf->getString(self::LDAP_HOSTNAME);
        $ldapUser = $conf->getString(self::LDAP_USER, null);
        $ldapPassword = $conf->getString(self::LDAP_PASSWORD, null);
        $this->ldapBase = $conf->getString(self::LDAP_BASE);
        $ldapEnableTLS = $conf->getBoolean(self::LDAP_TLS, false);

        $this->connector = new LdapConnector($ldapHostname, $ldapUser, $ldapPassword, $ldapEnableTLS);
        $this->fallbackAdapter = new AdapterRpc();
    }

    public function getPerunUser($idpEntityId, $uids)
    {
        # Build a LDAP query, we are searching for the user who has at least one of the uid
        $query = '';
        foreach ($uids as $uid) {
            $query .= '(eduPersonPrincipalNames=' . $uid . ')';
        }

        if (empty($query)) {
            return null;
        }

        $user = $this->connector->searchForEntity(
            'ou=People,' . $this->ldapBase,
            '(|' . $query . ')',
            ['perunUserId', 'displayName', 'cn', 'givenName', 'sn', 'preferredMail', 'mail']
        );
        if ($user === null) {
            return $user;
        }

        if (isset($user['displayName'][0])) {
            $name = $user['displayName'][0];
        } elseif (isset($user['cn'][0])) {
            $name = $user['cn'][0];
        } else {
            $name = null;
        }
        return new User($user['perunUserId'][0], $name);
    }

    public function getMemberGroups($user, $vo)
    {
        $userId = $user->getId();
        $userWithMembership = $this->connector->searchForEntity(
            'perunUserId=' . $userId . ',ou=People,' . $this->ldapBase,
            '(objectClass=perunUser)',
            ['perunUserId', 'memberOf']
        );

        $groups = [];
        foreach ($userWithMembership['memberOf'] as $groupDn) {
            $voId = explode('=', explode(',', $groupDn)[1], 2)[1];
            if ($voId !== $vo->getId()) {
                continue;
            }

            $group = $this->connector->searchForEntity(
                $groupDn,
                '(objectClass=perunGroup)',
                ['perunGroupId', 'cn', 'perunUniqueGroupName', 'perunVoId', 'uuid', 'description']
            );
            array_push(
                $groups,
                new Group(
                    $group['perunGroupId'][0],
                    $group['perunVoId'][0],
                    $group['uuid'][0],
                    $group['cn'][0],
                    $group['perunUniqueGroupName'][0],
                    $group['description'][0] ?? ''
                )
            );
        }

        return $groups;
    }

    public function getSpGroups($spEntityId)
    {
        $facility = $this->getFacilityByEntityId($spEntityId);

        if ($facility === null) {
            return [];
        }

        $id = $facility->getId();

        $resources = $this->connector->searchForEntities(
            $this->ldapBase,
            '(&(objectClass=perunResource)(perunFacilityDn=perunFacilityId=' . $id . ',' . $this->ldapBase . '))',
            ['perunResourceId', 'assignedGroupId', 'perunVoId']
        );

        $groups = [];
        foreach ($resources as $resource) {
            if (isset($resource['assignedGroupId'])) {
                foreach ($resource['assignedGroupId'] as $groupId) {
                    $group = $this->connector->searchForEntity(
                        'perunGroupId=' . $groupId . ',perunVoId=' . $resource['perunVoId'][0] . ',' . $this->ldapBase,
                        '(objectClass=perunGroup)',
                        ['perunGroupId', 'cn', 'perunUniqueGroupName', 'perunVoId', 'uuid', 'description']
                    );
                    array_push(
                        $groups,
                        new Group(
                            $group['perunGroupId'][0],
                            $group['perunVoId'][0],
                            $group['uuid'][0],
                            $group['cn'],
                            $group['perunUniqueGroupName'][0],
                            $group['description'][0] ?? ''
                        )
                    );
                }
            }
        }
        $groups = $this->removeDuplicateEntities($groups);

        return $groups;
    }

    public function getGroupByName($vo, $name)
    {
        $voId = $vo->getId();
        $group = $this->connector->searchForEntity(
            'perunVoId=' . $voId . ',' . $this->ldapBase,
            '(&(objectClass=perunGroup)(perunUniqueGroupName=' . $name . '))',
            ['perunGroupId', 'cn', 'perunUniqueGroupName', 'perunVoId', 'uuid', 'description']
        );
        if ($group === null) {
            throw new Exception(
                'Group with name: $name in VO: ' . $vo->getName() . ' does not exists in Perun LDAP.'
            );
        }
        return new Group(
            $group['perunGroupId'][0],
            $group['perunVoId'][0],
            $group['uuId'][0],
            $group['cn'][0],
            $group['perunUniqueGroupName'][0],
            $group['description'][0] ?? ''
        );
    }

    public function getVoByShortName($voShortName)
    {
        $vo = $this->connector->searchForEntity(
            $this->ldapBase,
            '(&(objectClass=perunVo)(o=' . $voShortName . '))',
            ['perunVoId', 'o', 'description']
        );
        if ($vo === null) {
            throw new Exception('Vo with name: ' . $voShortName . ' does not exists in Perun LDAP.');
        }

        return new Vo($vo['perunVoId'][0], $vo['description'][0], $vo['o'][0]);
    }

    public function getVoById($id)
    {
        $vo = $this->connector->searchForEntity(
            $this->ldapBase,
            '(&(objectClass=perunVo)(perunVoId=' . $id . '))',
            ['o', 'description']
        );

        if ($vo === null) {
            throw new Exception('Vo with id: ' . $id . ' does not exists in Perun LDAP.');
        }

        return new Vo($id, $vo['description'][0], $vo['o'][0]);
    }

    public function getUserAttributes($user, $attrNames)
    {
        $userId = $user->getId();
        $attributes = $this->connector->searchForEntity(
            'perunUserId=' . $userId . ',ou=People,' . $this->ldapBase,
            '(objectClass=perunUser)',
            $attrNames
        );
        // user in ldap (simplified by LdapConnector method) is actually set of its attributes
        return $attributes;
    }

    public function getUserAttributesValues($user, $attributes)
    {
        $attrTypeMap = AttributeUtils::createLdapAttrNameTypeMap($attributes);

        $perunAttrs = $this->connector->searchForEntities(
            $this->ldapBase,
            '(&(objectClass=perunUser)(perunUserId=' . $user->getId() . '))',
            array_keys($attrTypeMap)
        );

        $attributesValues = [];

        foreach (array_keys($attrTypeMap) as $attrName) {
            $attributesValues[$attrTypeMap[$attrName][self::INTERNAL_ATTR_NAME]] =
                $this->setAttrValue($attrTypeMap, $perunAttrs[0], $attrName);
        }

        return $attributesValues;
    }

    public function getFacilitiesByEntityId($spEntityId)
    {
        return $this->fallbackAdapter->getFacilitiesByEntityId($spEntityId);
    }

    public function getFacilityByEntityId($spEntityId)
    {
        $ldapResult = $this->connector->searchForEntity(
            $this->ldapBase,
            '(&(objectClass=perunFacility)(entityID=' . $spEntityId . '))',
            [self::PERUN_FACILITY_ID, self::CN, self::DESCRIPTION]
        );

        if (empty($ldapResult)) {
            Logger::warning(
                'perun:AdapterLdap: No facility with entityID \'' . $spEntityId . '\' found.'
            );
            return null;
        }

        $facility = new Facility(
            $ldapResult[self::PERUN_FACILITY_ID][0],
            $ldapResult[self::CN][0],
            $ldapResult[self::DESCRIPTION][0],
            $spEntityId
        );

        return $facility;
    }

    public function getEntitylessAttribute($attrName)
    {
        return $this->fallbackAdapter->getEntitylessAttribute($attrName);
    }

    public function getVoAttributes($vo, $attrNames)
    {
        return $this->fallbackAdapter->getVoAttributes($vo, $attrNames);
    }

    public function getVoAttributesValues($vo, $attributes)
    {
        $attrTypeMap = AttributeUtils::createLdapAttrNameTypeMap($attributes);

        $perunAttrs = $this->connector->searchForEntities(
            $this->ldapBase,
            '(&(objectClass=perunVO)(perunVoId=' . $vo->getId() . '))',
            array_keys($attrTypeMap)
        );

        $attributesValues = [];

        foreach (array_keys($attrTypeMap) as $attrName) {
            $attributesValues[$attrTypeMap[$attrName][self::INTERNAL_ATTR_NAME]] =
                $this->setAttrValue($attrTypeMap, $perunAttrs[0], $attrName);
        }

        return $attributesValues;
    }

    public function getFacilityAttribute($facility, $attrName)
    {
        return $this->fallbackAdapter->getFacilityAttribute($facility, $attrName);
    }

    public function searchFacilitiesByAttributeValue($attribute)
    {
        return $this->fallbackAdapter->searchFacilitiesByAttributeValue($attribute);
    }

    public function getFacilityAttributes($facility, $attrNames)
    {
        return $this->fallbackAdapter->getFacilityAttributes($facility, $attrNames);
    }

    public function getFacilityAttributesValues($facility, $attributes)
    {
        $attrTypeMap = AttributeUtils::createLdapAttrNameTypeMap($attributes);

        $perunAttrs = $this->connector->searchForEntities(
            $this->ldapBase,
            '(&(objectClass=perunFacility)(perunFacilityId=' . $facility->getId() . '))',
            array_keys($attrTypeMap)
        );

        $attributesValues = [];

        foreach (array_keys($attrTypeMap) as $attrName) {
            $attributesValues[$attrTypeMap[$attrName][self::INTERNAL_ATTR_NAME]] =
                $this->setAttrValue($attrTypeMap, $perunAttrs[0], $attrName);
        }

        return $attributesValues;
    }

    public function getUserExtSource($extSourceName, $extSourceLogin)
    {
        return $this->fallbackAdapter->getUserExtSource($extSourceName, $extSourceLogin);
    }

    public function updateUserExtSourceLastAccess($userExtSource)
    {
        $this->fallbackAdapter->updateUserExtSourceLastAccess($userExtSource);
    }

    public function getUserExtSourceAttributes($userExtSourceId, $attrNames)
    {
        return $this->fallbackAdapter->getUserExtSourceAttributes($userExtSourceId, $attrNames);
    }

    public function setUserExtSourceAttributes($userExtSourceId, $attributes)
    {
        $this->fallbackAdapter->setUserExtSourceAttributes($userExtSourceId, $attributes);
    }

    public function getUsersGroupsOnFacility($spEntityId, $userId)
    {
        $facility = $this->getFacilityByEntityId($spEntityId);

        if ($facility === null) {
            return [];
        }

        $id = $facility->getId();

        $resources = $this->connector->searchForEntities(
            $this->ldapBase,
            '(&(objectClass=perunResource)(perunFacilityDn=perunFacilityId=' . $id . ',' . $this->ldapBase . '))',
            ['perunResourceId']
        );
        Logger::debug('Resources - ' . var_export($resources, true));

        if ($resources === null) {
            throw new Exception(
                'Service with spEntityId: ' . $spEntityId . ' hasn\'t assigned any resource.'
            );
        }
        $resourcesString = '(|';
        foreach ($resources as $resource) {
            $resourcesString .= '(assignedToResourceId=' . $resource['perunResourceId'][0] . ')';
        }
        $resourcesString .= ')';

        $resultGroups = [];
        $groups = $this->connector->searchForEntities(
            $this->ldapBase,
            '(&(uniqueMember=perunUserId=' . $userId . ', ou=People,' . $this->ldapBase . ')' . $resourcesString . ')',
            ['perunGroupId', 'cn', 'perunUniqueGroupName', 'perunVoId', 'uuid', 'description']
        );

        foreach ($groups as $group) {
            array_push(
                $resultGroups,
                new Group(
                    $group['perunGroupId'][0],
                    $group['perunVoId'][0],
                    $group['uuid'][0],
                    $group['cn'][0],
                    $group['perunUniqueGroupName'][0],
                    $group['description'][0] ?? ''
                )
            );
        }
        $resultGroups = $this->removeDuplicateEntities($resultGroups);
        Logger::debug('Groups - ' . var_export($resultGroups, true));
        return $resultGroups;
    }

    public function getMemberStatusByUserAndVo($user, $vo)
    {
        $groupId = $this->connector->searchForEntity(
            $this->ldapBase,
            '(&(objectClass=perunGroup)(cn=members)(perunVoId=' . $vo->getId() .
            ')(uniqueMember=perunUserId=' . $user->getId() . ', ou=People,' . $this->ldapBase . '))',
            ['perunGroupid']
        );

        if (empty($groupId)) {
            return Member::INVALID;
        }
        return Member::VALID;
    }

    public function getResourceCapabilities($entityId, $userGroups)
    {
        $facility = $this->getFacilityByEntityId($entityId);

        if ($facility === null) {
            return [];
        }

        $facilityId = $facility->getId();

        $resources = $this->connector->searchForEntities(
            $this->ldapBase,
            '(&(objectClass=perunResource)(perunFacilityDn=perunFacilityId=' . $facilityId . ','
            . $this->ldapBase . '))',
            [self::CAPABILITIES, self::ASSIGNED_GROUP_ID]
        );

        $userGroupsIds = [];
        foreach ($userGroups as $userGroup) {
            array_push($userGroupsIds, $userGroup->getId());
        }

        $resourceCapabilities = [];
        foreach ($resources as $resource) {
            if (
                !array_key_exists(self::ASSIGNED_GROUP_ID, $resource) ||
                !array_key_exists(self::CAPABILITIES, $resource)
            ) {
                continue;
            }
            foreach ($resource[self::ASSIGNED_GROUP_ID] as $groupId) {
                if (in_array($groupId, $userGroupsIds)) {
                    foreach ($resource[self::CAPABILITIES] as $resourceCapability) {
                        array_push($resourceCapabilities, $resourceCapability);
                    }
                    break;
                }
            }
        }

        return $resourceCapabilities;
    }

    public function getFacilityCapabilities($entityId)
    {
        $facilityCapabilities = $this->connector->searchForEntity(
            $this->ldapBase,
            '(&(objectClass=perunFacility)(entityID=' . $entityId . '))',
            [self::CAPABILITIES]
        );

        if (empty($facilityCapabilities)) {
            return [];
        }

        return $facilityCapabilities['capabilities'];
    }

    private function setAttrValue($attrsNameTypeMap, $attrsFromLdap, $attr)
    {
        if (!array_key_exists($attr, $attrsFromLdap) && $attrsNameTypeMap[$attr][self::TYPE] === self::TYPE_BOOL) {
            return false;
        } elseif (!array_key_exists($attr, $attrsFromLdap) && $attrsNameTypeMap[$attr][self::TYPE] === self::TYPE_MAP) {
            return [];
        } elseif (array_key_exists($attr, $attrsFromLdap) && $attrsNameTypeMap[$attr][self::TYPE] === self::TYPE_MAP) {
            return $attrsFromLdap[$attr];
        } elseif (array_key_exists($attr, $attrsFromLdap)) {
            return $attrsFromLdap[$attr][0];
        } else {
            return null;
        }
    }
}
