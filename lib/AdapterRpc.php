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
 * Class sspmod_perun_AdapterRpc
 *
 * Perun adapter which uses Perun RPC interface
 *
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
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
                if (! empty($user['titleBefore'])) {
                    $name .= $user['titleBefore'] . ' ';
                }
                if (! empty($user['titleBefore'])) {
                    $name .= $user['firstName'] . ' ';
                }
                if (! empty($user['titleBefore'])) {
                    $name .= $user['middleName'] . ' ';
                }
                if (! empty($user['titleBefore'])) {
                    $name .= $user['lastName'];
                }
                if (! empty($user['titleBefore'])) {
                    $name .= ' ' . $user['titleAfter'];
                }

                return new User($user['id'], $name);
            } catch (PerunException $e) {
                if ($e->getName() === 'UserExtSourceNotExistsException') {
                    continue;
                } elseif ($e->getName() === 'ExtSourceNotExistsException') {
                    // Because use of original/source entityID as extSourceName
                    continue;
                }
                throw $e;
            }
        }

        return $user;
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

    public function getSpGroups($spEntityId)
    {
        $facility = $this->getFacilityByEntityId($spEntityId);

        if ($facility === null) {
            return [];
        }

        $perunAttrs = $this->connector->get('facilitiesManager', 'getAssignedResources', [
            'facility' => $facility->getId(),
        ]);

        $resources = [];
        foreach ($perunAttrs as $perunAttr) {
            array_push(
                $resources,
                new Resource($perunAttr['id'], $perunAttr['voId'], $perunAttr['facilityId'], $perunAttr['name'])
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
                array_push(
                    $spGroups,
                    new Group(
                        $group['id'],
                        $group['voId'],
                        $group['uuid'],
                        $group['name'],
                        $uniqueName,
                        $group['description']
                    )
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

        if (! isset($perunAttrValues[0]['id'])) {
            return $attributes;
        }
        $attrId = $perunAttrValues[0]['id'];

        $perunAttrKeys = $this->connector->get('attributesManager', 'getEntitylessKeys', [
            'attributeDefinition' => $attrId,
        ]);

        for ($i = 0, $iMax = count($perunAttrKeys); $i < $iMax; $i++) {
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
        $perunAttr = $this->connector->get('attributesManager', 'getAttribute', [
            'facility' => $facility->getId(),
            'attributeName' => $attrName,
        ]);

        return $perunAttr['value'];
    }

    public function getUsersGroupsOnFacility($spEntityId, $userId)
    {
        $facility = $this->getFacilityByEntityId($spEntityId);
        $groups = [];

        if ($facility === null) {
            return $groups;
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

    public function getFacilityByEntityId($spEntityId)
    {
        $perunAttr = $this->connector->get('facilitiesManager', 'getFacilitiesByAttribute', [
            'attributeName' => 'urn:perun:facility:attribute-def:def:entityID',
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

    /**
     * Returns member by User and Vo
     *
     * @param User $user
     * @param Vo $vo
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
                'Member for User with name ' . $user->getName() . ' and Vo with shortName ' .
                $vo->getShortName() . 'does not exist in Perun!'
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
     * Returns true if entity has registration form, false otherwise
     *
     * @param $entityId
     * @param $entityName
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

    public function getResourceCapabilities($entityId, $userGroups)
    {
        $facility = $this->getFacilityByEntityId($entityId);

        if ($facility === null) {
            return [];
        }

        $resources = $this->connector->get('facilitiesManager', 'getAssignedResources', [
            'facility' => $facility->getId(),
        ]);

        $userGroupsIds = [];
        foreach ($userGroups as $userGroup) {
            array_push($userGroupsIds, $userGroup->getId());
        }

        $capabilities = [];
        foreach ($resources as $resource) {
            $resourceGroups = $this->connector->get('resourcesManager', 'getAssignedGroups', [
                'resource' => $resource['id'],
            ]);

            $resourceCapabilities = $this->connector->get('attributesManager', 'getAttribute', [
                'resource' => $resource['id'],
                'attributeName' => 'urn:perun:resource:attribute-def:def:capabilities',
            ])['value'];

            if ($resourceCapabilities === null) {
                continue;
            }

            foreach ($resourceGroups as $resourceGroup) {
                if (in_array($resourceGroup['id'], $userGroupsIds, true)) {
                    foreach ($resourceCapabilities as $capability) {
                        array_push($capabilities, $capability);
                    }
                    break;
                }
            }
        }

        return $capabilities;
    }

    public function getFacilityCapabilities($entityId)
    {
        $facility = $this->getFacilityByEntityId($entityId);

        if ($facility === null) {
            return [];
        }

        $facilityCapabilities = $this->connector->get('attributesManager', 'getAttribute', [
            'facility' => $facility->getId(),
            'attributeName' => 'urn:perun:facility:attribute-def:def:capabilities',
        ])['value'];

        if (empty($facilityCapabilities)) {
            $facilityCapabilities = [];
        }

        return $facilityCapabilities;
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
            $attributes[$attrNamesMap[$perunAttrName]] = [
                'id' => $perunAttr['id'],
                'name' => $attrNamesMap[$perunAttrName],
                'displayName' => $perunAttr['displayName'],
                'type' => $perunAttr['type'],
                'value' => $perunAttr['value'],
                'friendlyName' => $perunAttr['friendlyName'],
            ];
        }

        return $attributes;
    }
}
