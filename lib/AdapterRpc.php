<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Exception as PerunException;
use SimpleSAML\Module\perun\model\Facility;
use SimpleSAML\Module\perun\model\Group;
use SimpleSAML\Module\perun\model\Member;
use SimpleSAML\Module\perun\model\Resource;
use SimpleSAML\Module\perun\model\User;
use SimpleSAML\Module\perun\model\Vo;

/**
 * Class sspmod_perun_AdapterRpc.
 *
 * Perun adapter which uses Perun RPC interface
 */
class AdapterRpc extends Adapter
{
    public const DEFAULT_CONFIG_FILE_NAME = 'module_perun.php';

    public const RPC_URL = 'rpc.url';

    public const RPC_USER = 'rpc.username';

    public const RPC_PASSWORD = 'rpc.password';

    public const RPC_SERIALIZER = 'rpc.serializer';

    public const TYPE_INTEGER = 'java.lang.Integer';

    public const TYPE_BOOLEAN = 'java.lang.Boolean';

    public const TYPE_STRING = 'java.lang.String';

    public const TYPE_ARRAY = 'java.util.ArrayList';

    public const TYPE_MAP = 'java.util.LinkedHashMap';

    private const DEBUG_PREFIX = 'perun:AdapterRpc - ';

    protected $connector;

    private $rpcUrl;

    private $rpcUser;

    private $rpcPassword;

    private $rpcSerializer;

    public function __construct($configFileName = null)
    {
        if ($configFileName === null) {
            $configFileName = self::DEFAULT_CONFIG_FILE_NAME;
        }

        $conf = Configuration::getConfig($configFileName);

        $this->rpcUrl = $conf->getString(self::RPC_URL);
        $this->rpcUser = $conf->getString(self::RPC_USER);
        $this->rpcPassword = $conf->getString(self::RPC_PASSWORD);
        $this->rpcSerializer = $conf->getString(self::RPC_SERIALIZER, 'json');

        $this->connector = new RpcConnector($this->rpcUrl, $this->rpcUser, $this->rpcPassword, $this->rpcSerializer);
    }

    public function getPerunUser($idpEntityId, $uids)
    {
        $user = null;

        foreach ($uids as $uid) {
            try {
                $user = $this->connector->get('usersManager', 'getUserByExtSourceNameAndExtLogin', [
                    'extSourceName' => $idpEntityId,
                    'extLogin' => $uid,
                ]);

                $name = '';
                if (!empty($user['titleBefore'])) {
                    $name .= $user['titleBefore'] . ' ';
                }
                if (!empty($user['firstName'])) {
                    $name .= $user['firstName'] . ' ';
                }
                if (!empty($user['middleName'])) {
                    $name .= $user['middleName'] . ' ';
                }
                if (!empty($user['lastName'])) {
                    $name .= $user['lastName'];
                }
                if (!empty($user['titleAfter'])) {
                    $name .= ' ' . $user['titleAfter'];
                }

                return new User($user['id'], $name);
            } catch (PerunException $e) {
                if ($e->getName() === 'UserExtSourceNotExistsException') {
                    continue;
                }
                if ($e->getName() === 'ExtSourceNotExistsException') {
                    // Because use of original/source entityID as extSourceName
                    continue;
                }
                throw $e;
            }
        }

        return $user;
    }

    public function getPerunUserByAdditionalIdentifiers($idpEntityId, $uids)
    {
        return $this->getPerunUser($idpEntityId, $uids);
    }

    public function getMemberGroups($user, $vo)
    {
        try {
            $member = $this->connector->get('membersManager', 'getMemberByUser', [
                'vo' => $vo->getId(),
                'user' => $user->getId(),
            ]);

            $memberGroups = $this->connector->get('groupsManager', 'getAllMemberGroups', [
                'member' => $member['id'],
            ]);
        } catch (PerunException $e) {
            return [];
        }

        $convertedGroups = [];
        foreach ($memberGroups as $group) {
            try {
                $attr = $this->connector->get('attributesManager', 'getAttribute', [
                    'group' => $group['id'],
                    'attributeName' => 'urn:perun:group:attribute-def:virt:voShortName',
                ]);
                $uniqueName = $attr['value'] . ':' . $group['name'];
                array_push(
                    $convertedGroups,
                    new Group(
                        $group['id'],
                        $group['voId'],
                        $group['uuid'],
                        $group['name'],
                        $uniqueName,
                        $group['description']
                    )
                );
            } catch (PerunException $e) {
                continue;
            }
        }

        return $convertedGroups;
    }

    public function getGroupsWhereMemberIsActive($user, $vo)
    {
        try {
            $member = $this->connector->get('membersManager', 'getMemberByUser', [
                'vo' => $vo->getId(),
                'user' => $user->getId(),
            ]);

            $memberGroups = $this->connector->get('groupsManager', 'getGroupsWhereMemberIsActive', [
                'member' => $member['id'],
            ]);
        } catch (PerunException $e) {
            return [];
        }

        $convertedGroups = [];
        foreach ($memberGroups as $group) {
            try {
                $attr = $this->connector->get('attributesManager', 'getAttribute', [
                    'group' => $group['id'],
                    'attributeName' => 'urn:perun:group:attribute-def:virt:voShortName',
                ]);
                $uniqueName = $attr['value'] . ':' . $group['name'];
                array_push(
                    $convertedGroups,
                    new Group(
                        $group['id'],
                        $group['voId'],
                        $group['uuid'],
                        $group['name'],
                        $uniqueName,
                        $group['description']
                    )
                );
            } catch (PerunException $e) {
                continue;
            }
        }

        return $convertedGroups;
    }

    public function getSpGroups(string $spEntityId, string $entityIdAttr = 'perunFacilityAttr_entityID'): array
    {
        $facility = $this->getFacilityByEntityId($spEntityId, $entityIdAttr);

        if ($facility === null) {
            return [];
        }

        return $this->getSpGroupsByFacility($facility);
    }

    public function getSpGroupsByFacility(Facility $facility): array
    {
        $perunAttrs = $this->connector->get('facilitiesManager', 'getAssignedResources', [
            'facility' => $facility->getId(),
        ]);

        $resources = [];
        foreach ($perunAttrs as $perunAttr) {
            $resources[] = new Resource(
                $perunAttr['id'],
                $perunAttr['voId'],
                $perunAttr['facilityId'],
                $perunAttr['name']
            );
        }

        $spGroups = [];
        foreach ($resources as $resource) {
            $groups = $this->connector->get('resourcesManager', 'getAssignedGroups', [
                'resource' => $resource->getId(),
            ]);

            foreach ($groups as $group) {
                $attr = $this->connector->get('attributesManager', 'getAttribute', [
                    'group' => $group['id'],
                    'attributeName' => 'urn:perun:group:attribute-def:virt:voShortName',
                ]);
                $uniqueName = $attr['value'] . ':' . $group['name'];
                $spGroups[] = new Group(
                    $group['id'],
                    $group['voId'],
                    $group['uuid'],
                    $group['name'],
                    $uniqueName,
                    $group['description']
                );
            }
        }

        return $this->removeDuplicateEntities($spGroups);
    }

    public function getGroupByName($vo, $name)
    {
        $group = $this->connector->get('groupsManager', 'getGroupByName', [
            'vo' => $vo->getId(),
            'name' => $name,
        ]);
        $attr = $this->connector->get('attributesManager', 'getAttribute', [
            'group' => $group['id'],
            'attributeName' => 'urn:perun:group:attribute-def:virt:voShortName',
        ]);
        $uniqueName = $attr['value'] . ':' . $group['name'];

        return new Group(
            $group['id'],
            $group['voId'],
            $group['uuid'],
            $group['name'],
            $uniqueName,
            $group['description']
        );
    }

    public function getVoByShortName($voShortName)
    {
        $vo = $this->connector->get('vosManager', 'getVoByShortName', [
            'shortName' => $voShortName,
        ]);

        return new Vo($vo['id'], $vo['name'], $vo['shortName']);
    }

    public function getVoById($id)
    {
        $vo = $this->connector->get('vosManager', 'getVoById', [
            'id' => $id,
        ]);

        return new Vo($vo['id'], $vo['name'], $vo['shortName']);
    }

    public function getUserAttributes($user, $attrNames)
    {
        $attrNamesMap = AttributeUtils::getRpcAttrNames($attrNames);

        $perunAttrs = $this->connector->get('attributesManager', 'getAttributes', [
            'user' => $user->getId(),
            'attrNames' => array_keys($attrNamesMap),
        ]);

        return $this->getAttributes($perunAttrs, $attrNamesMap);
    }

    public function getUserAttributesValues($user, $attributes)
    {
        $perunAttrs = $this->getUserAttributes($user, $attributes);
        $attributesValues = [];

        foreach ($perunAttrs as $perunAttrName => $perunAttr) {
            $attributesValues[$perunAttrName] = $perunAttr['value'];
        }

        return $attributesValues;
    }

    public function getEntitylessAttribute($attrName)
    {
        $attributes = [];
        $perunAttrValues = $this->connector->get('attributesManager', 'getEntitylessAttributes', [
            'attrName' => AttributeUtils::getAttrName($attrName, self::RPC),
        ]);

        if (!isset($perunAttrValues[0]['id'])) {
            return $attributes;
        }
        $attrId = $perunAttrValues[0]['id'];

        $perunAttrKeys = $this->connector->get('attributesManager', 'getEntitylessKeys', [
            'attributeDefinition' => $attrId,
        ]);

        for ($i = 0, $iMax = count($perunAttrKeys); $i < $iMax; ++$i) {
            $key = $perunAttrKeys[$i];
            $value = $perunAttrValues[$i];
            $attributes[$key] = $value;
        }

        return $attributes;
    }

    public function getVoAttributes($vo, $attrNames)
    {
        $attrNamesMap = AttributeUtils::getRpcAttrNames($attrNames);

        $perunAttrs = $this->connector->get('attributesManager', 'getAttributes', [
            'vo' => $vo->getId(),
            'attrNames' => array_keys($attrNamesMap),
        ]);

        return $this->getAttributes($perunAttrs, $attrNamesMap);
    }

    public function getVoAttributesValues($vo, $attributes)
    {
        $perunAttrs = $this->getVoAttributes($vo, $attributes);
        $attributesValues = [];

        foreach ($perunAttrs as $perunAttrName => $perunAttr) {
            $attributesValues[$perunAttrName] = $perunAttr['value'];
        }

        return $attributesValues;
    }

    public function getFacilityAdmins($facility)
    {
        return $this->connector->get('facilitiesManager', 'getAdmins', [
            'facility' => $facility->getId(),
        ]);
    }

    public function getFacilityAttribute($facility, $attrName)
    {
        $attrNameRpc = AttributeUtils::getRpcAttrName($attrName);
        $perunAttr = $this->connector->get('attributesManager', 'getAttribute', [
            'facility' => $facility->getId(),
            'attributeName' => $attrNameRpc,
        ]);

        return $perunAttr['value'];
    }

    public function getUsersGroupsOnFacility($spEntityId, $userId, $entityIdAttr = 'perunFacilityAttr_entityID')
    {
        $facility = $this->getFacilityByEntityId($spEntityId, $entityIdAttr);

        return self::getUsersGroupsOnSp($facility, $userId);
    }

    public function getUsersGroupsOnSp($facility, $userId)
    {
        if ($facility === null) {
            return [];
        }

        $usersGroupsOnFacility = $this->connector->get(
            'usersManager',
            'getRichGroupsWhereUserIsActive',
            [
                'facility' => $facility->getId(),
                'user' => $userId,
                'attrNames' => ['urn:perun:group:attribute-def:virt:voShortName'],
            ]
        );

        $groups = [];

        foreach ($usersGroupsOnFacility as $usersGroupOnFacility) {
            if (isset($usersGroupOnFacility['attributes'][0]['friendlyName']) &&
                $usersGroupOnFacility['attributes'][0]['friendlyName'] === 'voShortName') {
                $uniqueName = $usersGroupOnFacility['attributes'][0]['value'] . ':' . $usersGroupOnFacility['name'];

                array_push($groups, new Group(
                    $usersGroupOnFacility['id'],
                    $usersGroupOnFacility['voId'],
                    $usersGroupOnFacility['uuid'],
                    $usersGroupOnFacility['name'],
                    $uniqueName,
                    $usersGroupOnFacility['description']
                ));
            }
        }

        return $this->removeDuplicateEntities($groups);
    }

    public function getFacilityByEntityId($spEntityId, $entityIdAttr = 'perunFacilityAttr_entityID')
    {
        $attrName = AttributeUtils::getRpcAttrName($entityIdAttr);
        if (empty($attrName)) {
            $attrName = 'urn:perun:facility:attribute-def:def:entityID';
            Logger::warning(
                "No attribute configuration in RPC found for attribute {$entityIdAttr}, using {$attrName} as fallback value"
            );
        }
        $perunAttr = $this->connector->get('facilitiesManager', 'getFacilitiesByAttribute', [
            'attributeName' => $attrName,
            'attributeValue' => $spEntityId,
        ]);

        if (empty($perunAttr)) {
            Logger::warning('perun:AdapterRpc: No facility with entityID \'' . $spEntityId . '\' found.');

            return null;
        }

        if (count($perunAttr) > 1) {
            Logger::warning(
                'perun:AdapterRpc: There is more than one facility with entityID \'' . $spEntityId . '.'
            );

            return null;
        }

        return new Facility($perunAttr[0]['id'], $perunAttr[0]['name'], $perunAttr[0]['description'], $spEntityId);
    }

    public function getFacilityByClientId($clientId, $clientIdAttr = 'perunFacilityAttr_OIDCClientID')
    {
        $attrName = AttributeUtils::getRpcAttrName($clientIdAttr);
        if (empty($attrName)) {
            $attrName = 'urn:perun:facility:attribute-def:def:OIDCClientID';
            Logger::warning(
                "No attribute configuration in RPC found for attribute {$clientIdAttr}, using {$attrName} as fallback value"
            );
        }
        $perunAttr = $this->connector->get('facilitiesManager', 'getFacilitiesByAttribute', [
            'attributeName' => $attrName,
            'attributeValue' => $clientId,
        ]);

        if (empty($perunAttr)) {
            Logger::warning('perun:AdapterRpc: No facility with clientId \'' . $clientId . '\' found.');

            return null;
        }

        if (count($perunAttr) > 1) {
            Logger::warning('perun:AdapterRpc: There is more than one facility with clientId \'' . $clientId . '.');

            return null;
        }

        return new Facility($perunAttr[0]['id'], $perunAttr[0]['name'], $perunAttr[0]['description'], $clientId);
    }

    /**
     * Returns member by User and Vo.
     *
     * @param User $user
     * @param Vo   $vo
     *
     * @return Member
     */
    public function getMemberByUser($user, $vo)
    {
        $member = $this->connector->get('membersManager', 'getMemberByUser', [
            'user' => $user->getId(),
            'vo' => $vo->getId(),
        ]);
        if ($member === null) {
            throw new Exception(
                'Member for User with name ' . $user->getName() . ' and Vo with shortName ' . $vo->getShortName() . 'does not exist in Perun!'
            );
        }

        return new Member($member['id'], $member['voId'], $member['status']);
    }

    public function isUserInVo($user, $voShortName)
    {
        if (empty($user->getId())) {
            throw new Exception('userId is empty');
        }
        if (empty($voShortName)) {
            throw new Exception('voShortName is empty');
        }

        $vo = $this->getVoByShortName($voShortName);
        if ($vo === null) {
            Logger::debug('isUserInVo - No VO found, returning false');

            return false;
        }

        return $this->getMemberStatusByUserAndVo($user, $vo) === Member::VALID;
    }

    /**
     * Returns true if entity has registration form, false otherwise.
     *
     * @param $entityId
     * @param $entityName
     *
     * @return bool
     */
    public function hasRegistrationForm($entityId, $entityName)
    {
        try {
            $this->connector->get('registrarManager', 'getApplicationForm', [
                $entityName => $entityId,
            ]);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function hasRegistrationFormByVoShortName($voShortName)
    {
        $vo = $this->getVoByShortName($voShortName);

        if (empty($vo)) {
            return false;
        }

        return $this->hasRegistrationForm($vo->getId(), 'vo');
    }

    public function searchFacilitiesByAttributeValue($attribute)
    {
        $perunAttrs = $this->connector->post('searcher', 'getFacilities', [
            'attributesWithSearchingValues' => $attribute,
        ]);
        $facilities = [];
        foreach ($perunAttrs as $perunAttr) {
            array_push(
                $facilities,
                new Facility($perunAttr['id'], $perunAttr['name'], $perunAttr['description'], null)
            );
        }

        return $facilities;
    }

    public function getFacilityAttributes($facility, $attrNames)
    {
        $attrNamesMap = AttributeUtils::getRpcAttrNames($attrNames);

        $perunAttrs = $this->connector->get('attributesManager', 'getAttributes', [
            'facility' => $facility->getId(),
            'attrNames' => array_keys($attrNamesMap),
        ]);

        return $this->getAttributes($perunAttrs, $attrNamesMap);
    }

    public function getFacilityAttributesValues($facility, $attributes)
    {
        $perunAttrs = $this->getFacilityAttributes($facility, $attributes);
        $attributesValues = [];

        foreach ($perunAttrs as $perunAttrName => $perunAttr) {
            $attributesValues[$perunAttrName] = $perunAttr['value'];
        }

        return $attributesValues;
    }

    public function getUserExtSource($extSourceName, $extSourceLogin)
    {
        return $this->connector->get('usersManager', 'getUserExtSourceByExtLoginAndExtSourceName', [
            'extSourceName' => $extSourceName,
            'extSourceLogin' => $extSourceLogin,
        ]);
    }

    public function updateUserExtSourceLastAccess($userExtSource)
    {
        $this->connector->post('usersManager', 'updateUserExtSourceLastAccess', [
            'userExtSource' => $userExtSource,
        ]);
    }

    public function getUserExtSourceAttributes($userExtSourceId, $attrNames)
    {
        $attrNamesMap = AttributeUtils::getRpcAttrNames($attrNames);

        $perunAttrs = $this->connector->get('attributesManager', 'getAttributes', [
            'userExtSource' => $userExtSourceId,
            'attrNames' => array_keys($attrNamesMap),
        ]);

        return $this->getAttributes($perunAttrs, $attrNamesMap);
    }

    public function setUserExtSourceAttributes($userExtSourceId, $attributes)
    {
        $this->connector->post('attributesManager', 'setAttributes', [
            'userExtSource' => $userExtSourceId,
            'attributes' => $attributes,
        ]);
    }

    public function getMemberStatusByUserAndVo($user, $vo)
    {
        try {
            $member = $this->getMemberByUser($user, $vo);
        } catch (Exception $ex) {
            return null;
        }

        return $member->getStatus();
    }

    public function getResourceCapabilities(string $spEntityId, array $userGroups, string $entityIdAttr): array
    {
        if (empty($spEntityId)) {
            Logger::warning(
                self::DEBUG_PREFIX . 'getResourceCapabilities - empty spEntityId provided, returning empty list of resource capabilities.'
            );
            return [];
        } elseif (empty($userGroups)) {
            Logger::warning(
                self::DEBUG_PREFIX . 'getResourceCapabilities - empty userGroups provided, returning empty list of resource capabilities.'
            );
            return [];
        }

        $facility = $this->getFacilityByEntityId($spEntityId, $entityIdAttr);
        if ($facility === null || $facility->getId() === null) {
            Logger::warning(
                self::DEBUG_PREFIX . sprintf(
                    'getResourceCapabilities - no facility (or facility with null ID) found four EntityID \'%s\', returning empty list of resource capabilities.',
                    $spEntityId
                )
            );
            return [];
        }

        $resources = $this->getAssignedResources($facility->getId());
        if (empty($resources)) {
            Logger::debug(
                self::DEBUG_PREFIX . sprintf(
                    'getResourceCapabilities - no resources found for SP with EntityID \'%s\', returning empty list of resource capabilities.',
                    $spEntityId
                )
            );
            return [];
        }

        $userGroupsIds = [];
        foreach ($userGroups as $userGroup) {
            if ($userGroup === null || $userGroup->getId() === null) {
                Logger::debug(
                    self::DEBUG_PREFIX . 'getResourceCapabilities - skipping user group due to null group or null group ID.'
                );
                continue;
            }
            $userGroupsIds[] = $userGroup->getId();
        }

        $capabilities = [];
        foreach ($resources as $resource) {
            if ($resource === null || $resource->getId() === null) {
                Logger::debug(
                    self::DEBUG_PREFIX . 'getResourceCapabilities - skipping resource due to null resource or null resource ID.'
                );
                continue;
            }
            $resourceCapabilities = $this->connector->get('attributesManager', 'getAttribute', [
                'resource' => $resource->getId(),
                'attributeName' => 'urn:perun:resource:attribute-def:def:capabilities',
            ]);

            if (empty($resourceCapabilities['value'])) {
                Logger::debug(
                    self::DEBUG_PREFIX . 'getResourceCapabilities - skipping resource due to empty capabilities.'
                );
                continue;
            }
            $resourceCapabilities = $resourceCapabilities['value'];

            $resourceGroups = $this->connector->get('resourcesManager', 'getAssignedGroups', [
                'resource' => $resource->getId(),
            ]);

            if (empty($resourceGroups)) {
                continue;
            }

            foreach ($resourceGroups as $resourceGroup) {
                if (($resourceGroup['id'] ?? null) === null) {
                    Logger::debug(
                        self::DEBUG_PREFIX . 'getResourceCapabilities - skipping resource group due to missing group ID.'
                    );
                    continue;
                }
                if (in_array($resourceGroup['id'], $userGroupsIds, true)) {
                    $capabilities = array_merge($capabilities, $resourceCapabilities);
                    break;
                }
            }
        }

        return array_values(array_unique($capabilities));
    }

    public function getFacilityCapabilities(string $spEntityId, string $entityIdAttr): array
    {
        if (empty($spEntityId)) {
            Logger::warning(
                self::DEBUG_PREFIX . 'getFacilityCapabilities - empty spEntityId provided, returning empty list of facility capabilities.'
            );
            return [];
        }
        $facility = $this->getFacilityByEntityId($spEntityId, $entityIdAttr);

        if ($facility === null) {
            Logger::warning(
                self::DEBUG_PREFIX . sprintf(
                    'getFacilityCapabilities - no facility found four EntityID \'%s\', returning empty list of facility capabilities.',
                    $spEntityId
                )
            );
            return [];
        }

        $facilityCapabilities = $this->connector->get('attributesManager', 'getAttribute', [
            'facility' => $facility->getId(),
            'attributeName' => 'urn:perun:facility:attribute-def:def:capabilities',
        ]);

        if (empty($facilityCapabilities['value'])) {
            Logger::debug(
                self::DEBUG_PREFIX . 'getFacilityCapabilities - empty or missing value of facility capabilities attribute detected, returning empty list of facility capabilities.'
            );
            return [];
        }
        if (!is_array($facilityCapabilities['value'])) {
            $facilityCapabilities['value'] = [$facilityCapabilities['value']];
        }
        return array_values(array_unique($facilityCapabilities['value']));
    }

    public function getAttributesDefinition()
    {
        return $this->connector->get('attributesManager', 'getAttributesDefinition');
    }

    public function setFacilityAttributes($facilityId, $attributes)
    {
        $this->connector->post('attributesManager', 'setAttributes', [
            'facility' => $facilityId,
            'attributes' => $attributes,
        ]);
    }

    public function createFacility($facility)
    {
        return $this->connector->post('facilitiesManager', 'createFacility', $facility);
    }

    private function getAttributes($perunAttrs, $attrNamesMap)
    {
        $attributes = [];

        foreach ($perunAttrs as $perunAttr) {
            $perunAttrName = $perunAttr['namespace'] . ':' . $perunAttr['friendlyName'];
            $attribute = [];
            foreach (array_keys($perunAttr) as $key) {
                $attribute[$key] = $perunAttr[$key];
            }

            $attribute['name'] = $attrNamesMap[$perunAttrName];
            $attributes[$attrNamesMap[$perunAttrName]] = $attribute;
        }

        return $attributes;
    }

    private function getAssignedResources(int $facilityId): array
    {
        $perunResources = $this->connector->get('facilitiesManager', 'getAssignedResources', [
            'facility' => $facilityId,
        ]);

        return empty($perunResources) ? [] : array_map(function ($resource) {
            return new Resource($resource['id'], $resource['voId'], $resource['facilityId'], $resource['name']);
        }, array_filter($perunResources));
    }
}
