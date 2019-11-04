<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
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
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class AdapterRpc extends Adapter
{
    const DEFAULT_CONFIG_FILE_NAME = 'module_perun.php';

    const RPC_URL = 'rpc.url';

    const RPC_USER = 'rpc.username';

    const RPC_PASSWORD = 'rpc.password';

    protected $connector;

    private $rpcUrl;

    private $rpcUser;

    private $rpcPassword;

    public function __construct($configFileName = null)
    {
        if ($configFileName === null) {
            $configFileName = self::DEFAULT_CONFIG_FILE_NAME;
        }

        $conf = Configuration::getConfig($configFileName);

        $this->rpcUrl = $conf->getString(self::RPC_URL);
        $this->rpcUser = $conf->getString(self::RPC_USER);
        $this->rpcPassword = $conf->getString(self::RPC_PASSWORD);

        $this->connector = new RpcConnector($this->rpcUrl, $this->rpcUser, $this->rpcPassword);
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
                if (!empty($user['titleBefore'])) {
                    $name .= $user['firstName'] . ' ';
                }
                if (!empty($user['titleBefore'])) {
                    $name .= $user['middleName'] . ' ';
                }
                if (!empty($user['titleBefore'])) {
                    $name .= $user['lastName'];
                }
                if (!empty($user['titleBefore'])) {
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
        $perunAttr = $this->connector->get('facilitiesManager', 'getFacilitiesByAttribute', [
            'attributeName' => 'urn:perun:facility:attribute-def:def:entityID',
            'attributeValue' => $spEntityId,
        ])[0];
        $facility = new Facility(
            $perunAttr['id'],
            $perunAttr['name'],
            $perunAttr['description'],
            $spEntityId
        );

        $perunAttrs = $this->connector->get('facilitiesManager', 'getAssignedResources', [
            'facility' => $facility->getId(),
        ]);

        $resources = [];
        foreach ($perunAttrs as $perunAttr) {
            array_push(
                $resources,
                new Resource(
                    $perunAttr['id'],
                    $perunAttr['voId'],
                    $perunAttr['facilityId'],
                    $perunAttr['name']
                )
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
        $perunAttrs = $this->connector->get('attributesManager', 'getAttributes', [
            'user' => $user->getId(),
            'attrNames' => $attrNames,
        ]);

        $attributes = [];
        foreach ($perunAttrs as $perunAttr) {
            $perunAttrName = $perunAttr['namespace'] . ':' . $perunAttr['friendlyName'];

            $attributes[$perunAttrName] = $perunAttr['value'];
        }

        return $attributes;
    }

    public function getEntitylessAttribute($attrName)
    {
        $attributes = [];

        $perunAttrValues = $this->connector->get('attributesManager', 'getEntitylessAttributes', [
            'attrName' => $attrName,
        ]);

        if (!isset($perunAttrValues[0]['id'])) {
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
        $perunAttrs = $this->connector->get('attributesManager', 'getAttributes', [
            'vo' => $vo->getId(),
            'attrNames' => $attrNames,
        ]);

        $attributes = [];
        foreach ($perunAttrs as $perunAttr) {
            $perunAttrName = $perunAttr['namespace'] . ':' . $perunAttr['friendlyName'];

            $attributes[$perunAttrName] = $perunAttr['value'];
        }

        return $attributes;
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
        $facilities = $this->connector->get('facilitiesManager', 'getFacilitiesByAttribute', [
            'attributeName' => 'urn:perun:facility:attribute-def:def:entityID',
            'attributeValue' => $spEntityId,
        ]);

        $groups = [];

        foreach ($facilities as $facility) {
            $usersGroupsOnFacility = $this->connector->get(
                'usersManager',
                'getRichGroupsWhereUserIsActive',
                [
                    'facility' => $facility['id'],
                    'user' => $userId,
                    'attrNames' => ['urn:perun:group:attribute-def:virt:voShortName'],
                ]
            );

            foreach ($usersGroupsOnFacility as $usersGroupOnFacility) {
                if (
                    isset($usersGroupOnFacility['attributes'][0]['friendlyName']) &&
                    $usersGroupOnFacility['attributes'][0]['friendlyName'] === 'voShortName'
                ) {
                    $uniqueName = $usersGroupOnFacility['attributes'][0]['value'] . ':' . $usersGroupOnFacility['name'];

                    array_push($groups, new Group(
                        $usersGroupOnFacility['id'],
                        $usersGroupOnFacility['voId'],
                        $usersGroupOnFacility['name'],
                        $uniqueName,
                        $usersGroupOnFacility['description']
                    ));
                }
            }
        }
        return $this->removeDuplicateEntities($groups);
    }

    public function getFacilitiesByEntityId($spEntityId)
    {
        $perunAttrs = $this->connector->get('facilitiesManager', 'getFacilitiesByAttribute', [
            'attributeName' => 'urn:perun:facility:attribute-def:def:entityID',
            'attributeValue' => $spEntityId,
        ]);
        $facilities = [];
        foreach ($perunAttrs as $perunAttr) {
            array_push(
                $facilities,
                new Facility(
                    $perunAttr['id'],
                    $perunAttr['name'],
                    $perunAttr['description'],
                    $spEntityId
                )
            );
        }
        return $facilities;
    }

    /**
     * Returns member by User and Vo
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

    /**
     * Returns true if group has registration form, false otherwise
     * @param Group $group
     * @return bool
     */
    public function hasRegistrationForm($group)
    {
        try {
            $this->connector->get('registrarManager', 'getApplicationForm', [
                'group' => $group->getId(),
            ]);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
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
                new Facility(
                    $perunAttr['id'],
                    $perunAttr['name'],
                    $perunAttr['description'],
                    null
                )
            );
        }
        return $facilities;
    }

    public function getFacilityAttributes($facility, $attrNames)
    {
        $perunAttrs = $this->connector->get('attributesManager', 'getAttributes', [
            'facility' => $facility->getId(),
            'attrNames' => $attrNames,
        ]);
        $attributes = [];
        foreach ($perunAttrs as $perunAttr) {
            array_push($attributes, [
                'id' => $perunAttr['id'],
                'name' => $perunAttr['namespace'] . ':' . $perunAttr['friendlyName'],
                'displayName' => $perunAttr['displayName'],
                'type' => $perunAttr['type'],
                'value' => $perunAttr['value'],
            ]);
        }
        return $attributes;
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
        return $this->connector->get('attributesManager', 'getAttributes', [
            'userExtSource' => $userExtSourceId,
            'attrNames' => $attrNames,
        ]);
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
        $this->connector->post('facilitiesManager', 'createFacility', $facility);
    }
}
