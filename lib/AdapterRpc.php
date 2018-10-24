<?php

/**
 * Class sspmod_perun_AdapterRpc
 *
 * Perun adapter which uses Perun RPC interface
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class sspmod_perun_AdapterRpc extends sspmod_perun_Adapter
{
	const DEFAULT_CONFIG_FILE_NAME = 'module_perun.php';
	const RPC_URL  = 'rpc.url';
	const RPC_USER = 'rpc.username';
	const RPC_PASSWORD = 'rpc.password';

	private $rpcUrl;
	private $rpcUser;
	private $rpcPassword;

	protected $connector;

	public function __construct ($configFileName = null)
	{
		if (is_null($configFileName)) {
			$configFileName = self::DEFAULT_CONFIG_FILE_NAME;
		}

		$conf = SimpleSAML_Configuration::getConfig($configFileName);

		$this->rpcUrl = $conf->getString(self::RPC_URL);
		$this->rpcUser = $conf->getString(self::RPC_USER);
		$this->rpcPassword = $conf->getString(self::RPC_PASSWORD);

		$this->connector = new sspmod_perun_RpcConnector($this->rpcUrl, $this->rpcUser, $this->rpcPassword);
	}

	public function getPerunUser($idpEntityId, $uids)
	{
		$user = null;

		foreach ($uids as $uid) {
			try {
				$user = $this->connector->get('usersManager', 'getUserByExtSourceNameAndExtLogin', array(
					'extSourceName' => $idpEntityId,
					'extLogin' => $uid,
				));

				$name = '';
				if (!empty($user['titleBefore'])) $name .= $user['titleBefore'].' ';
				if (!empty($user['titleBefore'])) $name .= $user['firstName'].' ';
				if (!empty($user['titleBefore'])) $name .= $user['middleName'].' ';
				if (!empty($user['titleBefore'])) $name .= $user['lastName'];
				if (!empty($user['titleBefore'])) $name .= ' '.$user['titleAfter'];

				return new sspmod_perun_model_User($user['id'], $name);
			} catch (sspmod_perun_Exception $e) {
				if ($e->getName() === 'UserExtSourceNotExistsException') {
					continue;
				} else if ($e->getName() === 'ExtSourceNotExistsException') {
					// Because use of original/source entityID as extSourceName
					continue;
				} else {
					throw $e;
				}
			}
		}

		return $user;
	}


	public function getMemberGroups($user, $vo)
	{
		try {
			$member = $this->connector->get('membersManager', 'getMemberByUser', array(
				'vo' => $vo->getId(),
				'user' => $user->getId(),
			));
		

			$memberGroups = $this->connector->get('groupsManager', 'getAllMemberGroups', array(
				'member' => $member['id'],
			));
		} catch (sspmod_perun_Exception $e) {
                        return array();
                }

		$convertedGroups = array();
		foreach ($memberGroups as $group) {
			try {
				$attr = $this->connector->get('attributesManager', 'getAttribute', array(
					'group' => $group['id'],
					'attributeName' => 'urn:perun:group:attribute-def:virt:voShortName'
				));
				$uniqueName = $attr['value'] . ":" . $group['name'];
				array_push($convertedGroups, new sspmod_perun_model_Group($group['id'], $group['voId'], $group['name'], $uniqueName, $group['description']));
			} catch (sspmod_perun_Exception $e) {
				continue;
			}
		}

		return $convertedGroups;
	}


	public function getSpGroups($spEntityId)
	{
		$perunAttr = $this->connector->get('facilitiesManager', 'getFacilitiesByAttribute', array(
			'attributeName' => 'urn:perun:facility:attribute-def:def:entityID',
			'attributeValue' => $spEntityId,
		))[0];
		$facility = new sspmod_perun_model_Facility($perunAttr['id'], $perunAttr['name'], $perunAttr['description'], $spEntityId);

		$perunAttrs = $this->connector->get('facilitiesManager', 'getAssignedResources', array(
			'facility' => $facility->getId(),
		));

		$resources = array();
		foreach ($perunAttrs as $perunAttr) {
			array_push($resources, new sspmod_perun_model_Resource($perunAttr['id'], $perunAttr['voId'], $perunAttr['facilityId'], $perunAttr['name']));
		}

		$spGroups = array();
		foreach ($resources as $resource) {
			$groups = $this->connector->get('resourcesManager', 'getAssignedGroups', array(
				'resource' => $resource->getId(),
			));

			foreach ($groups as $group) {
				$attr = $this->connector->get('attributesManager', 'getAttribute', array(
					'group' => $group['id'],
					'attributeName' => 'urn:perun:group:attribute-def:virt:voShortName'
				));
				$uniqueName = $attr['value'] . ":" . $group['name'];
				array_push($spGroups, new sspmod_perun_model_Group($group['id'],$group['voId'], $group['name'], $uniqueName, $group['description']));
			}
		}

		$spGroups = $this->removeDuplicateEntities($spGroups);

		return $spGroups;
	}


	public function getGroupByName($vo, $name)
	{
		$group = $this->connector->get('groupsManager', 'getGroupByName', array(
			'vo' => $vo->getId(),
			'name' => $name,
		));
		$attr = $this->connector->get('attributesManager', 'getAttribute', array(
			'group' => $group['id'],
			'attributeName' => 'urn:perun:group:attribute-def:virt:voShortName'
		));
		$uniqueName = $attr['value'] . ":" . $group['name'];
		return new sspmod_perun_model_Group($group['id'], $group['voId'], $group['name'], $uniqueName, $group['description']);
	}


	public function getVoByShortName($voShortName)
	{
		$vo = $this->connector->get('vosManager', 'getVoByShortName', array(
			'shortName' => $voShortName,
		));

		return new sspmod_perun_model_Vo($vo['id'], $vo['name'], $vo['shortName']);
	}

	public function getVoById($id)
	{
		$vo = $this->connector->get('vosManager', 'getVoById', array(
			'id' => $id,
		));

		return new sspmod_perun_model_Vo($vo['id'], $vo['name'], $vo['shortName']);
	}

	public function getUserAttributes($user, $attrNames)
	{
		$perunAttrs = $this->connector->get('attributesManager', 'getAttributes', array(
			'user' => $user->getId(),
			'attrNames' => $attrNames,
		));

		$attributes = array();
		foreach ($perunAttrs as $perunAttr) {

			$perunAttrName = $perunAttr['namespace'] . ":" . $perunAttr['friendlyName'];

			$attributes[$perunAttrName] = $perunAttr['value'];
		}

		return $attributes;
	}

	public function getEntitylessAttribute($attrName)
	{
		$perunAttrs = $this->connector->get('attributesManager', 'getEntitylessAttributes', array(
			'attrName' => $attrName,
		));

		$attributes = array();
		foreach ($perunAttrs as $perunAttr) {
			$attributes[key($perunAttr['value'])] = $perunAttr['value'][key($perunAttr['value'])];
		}

		return $attributes;

	}

	public function getVoAttributes($vo, $attrNames)
	{
		$perunAttrs = $this->connector->get('attributesManager', 'getAttributes', array(
			'vo' => $vo->getId(),
			'attrNames' => $attrNames,
		));

		$attributes = array();
		foreach ($perunAttrs as $perunAttr) {

			$perunAttrName = $perunAttr['namespace'] . ":" . $perunAttr['friendlyName'];

			$attributes[$perunAttrName] = $perunAttr['value'];
		}

		return $attributes;
	}

	public function getFacilityAttribute($facility, $attrName)
	{
		$perunAttr = $this->connector->get('attributesManager', 'getAttribute', array(
			'facility' => $facility->getId(),
			'attributeName' => $attrName,
		));

		return $perunAttr['value'];
	}


	public function getUsersGroupsOnFacility($spEntityId, $userId)
	{
		$facilities = $this->connector->get('facilitiesManager', 'getFacilitiesByAttribute', array(
			'attributeName' => 'urn:perun:facility:attribute-def:def:entityID',
			'attributeValue' => $spEntityId,
		));

		$allowedResources = array();
		foreach ($facilities as $facility) {
			$resources = $this->connector->get('facilitiesManager', 'getAssignedResources', array(
				'facility' => $facility['id'],
			));
			$allowedResources = array_merge($allowedResources, $resources);
		}

		$members = $this->connector->get('membersManager', 'getMembersByUser', array(
			'user' => $userId,
		));

		$validMembers = array();
		foreach ($members as $member) {
			if ($member['status'] === 'VALID') {
				array_push($validMembers, $member);
			}
		}

		$allGroups = array();
		foreach ($allowedResources as $resource) {
			foreach ($validMembers as $member) {
				$groups = $this->connector->get('resourcesManager', 'getAssignedGroups', array(
					'resource' => $resource['id'],
					'member' => $member['id'],
				));
				foreach ($groups as $group) {
					$attr = $this->connector->get('attributesManager', 'getAttribute', array(
						'group' => $group['id'],
						'attributeName' => 'urn:perun:group:attribute-def:virt:voShortName'
					));
					$uniqueName = $attr['value'] . ":" . $group['name'];
					array_push($allGroups, new sspmod_perun_model_Group($group['id'], $group['voId'],  $group['name'], $uniqueName, $group['description']));
				}
			}
		}

		$allGroups = $this->removeDuplicateEntities($allGroups);
		return $allGroups;
	}

	public function getFacilitiesByEntityId($spEntityId)
	{
		$perunAttrs = $this->connector->get('facilitiesManager', 'getFacilitiesByAttribute', array(
			'attributeName' => 'urn:perun:facility:attribute-def:def:entityID',
			'attributeValue' => $spEntityId,
		));
		$facilities = array();
		foreach ($perunAttrs as $perunAttr) {
			array_push($facilities, new sspmod_perun_model_Facility($perunAttr['id'], $perunAttr['name'], $perunAttr['description'], $spEntityId));
		}
		return $facilities;
	}

	/**
	 * Returns member by User and Vo
	 * @param sspmod_perun_model_User $user
	 * @param sspmod_perun_model_Vo $vo
	 * @return sspmod_perun_model_Member
	 */
	public function getMemberByUser($user, $vo) {
		$member = $this->connector->get('membersManager', 'getMemberByUser', array(
			'user' => $user->getId(),
			'vo' => $vo->getId(),
		));
		if (is_null($member)) {
			throw new SimpleSAML_Error_Exception("Member for User with name " . $user->getName() . " and Vo with shortName " .
			$vo->getShortName() . "does not exist in Perun!");
		}
		return new sspmod_perun_model_Member($member['id'], $member['voId'], $member['status']);
	}

	/**
	 * Returns true if group has registration form, false otherwise
	 * @param sspmod_perun_model_Group $group
	 * @return bool
	 */
	public function hasRegistrationForm($group) {
		try {
			$this->connector->get( 'registrarManager', 'getApplicationForm', array(
				'group' => $group->getId(),
			));
			return true;
		} catch (Exception $exception) {
			return false;
		}
	}

	public function searchFacilitiesByAttributeValue($attribute)
	{
		$perunAttrs = $this->connector->post('searcher', 'getFacilities', array(
			'attributesWithSearchingValues' => $attribute,
		));
		$facilities = array();
		foreach($perunAttrs as $perunAttr) {
			array_push($facilities, new sspmod_perun_model_Facility($perunAttr['id'], $perunAttr['name'], $perunAttr['description'],null));
		}
		return $facilities;
	}

	public function getFacilityAttributes($facility, $attrNames) {
		$perunAttrs = $this->connector->get('attributesManager', 'getAttributes', array(
			'facility' => $facility->getId(),
			'attrNames' => $attrNames,
		));
		$attributes = array();
		foreach($perunAttrs as $perunAttr) {
			array_push($attributes, array(
				'id' => $perunAttr['id'],
				'name' => $perunAttr['namespace'] . ':' . $perunAttr['friendlyName'],
				'displayName' => $perunAttr['displayName'],
				'type' => $perunAttr['type'],
				'value' => $perunAttr['value']
			));
		}
		return $attributes;
	}

	public function getUserExtSource($extSourceName, $extSourceLogin) {
		return $this->connector->get('usersManager', 'getUserExtSourceByExtLoginAndExtSourceName', array(
			"extSourceName" => $extSourceName,
			"extSourceLogin" => $extSourceLogin
		));
	}

	public function updateUserExtSourceLastAccess($userExtSource) {
		$this->connector->post( 'usersManager', 'updateUserExtSourceLastAccess', array(
			"userExtSource" => $userExtSource
		));
	}

	public function getUserExtSourceAttributes($userExtSourceId, $attrNames)
	{
		return $this->connector->get('attributesManager', 'getAttributes', array(
			"userExtSource" => $userExtSourceId,
			"attrNames" => $attrNames
		));
	}

	public function setUserExtSourceAttributes($userExtSourceId, $attributes)
	{
		$this->connector->post('attributesManager', 'setAttributes', array(
			"userExtSource" => $userExtSourceId,
			"attributes" => $attributes
		));
	}
}
