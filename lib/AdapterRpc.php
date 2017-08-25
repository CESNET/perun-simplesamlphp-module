<?php

/**
 * Class sspmod_perun_AdapterRpc
 *
 * Perun adapter which uses Perun RPC interface
 */
class sspmod_perun_AdapterRpc extends sspmod_perun_Adapter
{


	public function getPerunUser($idpEntityId, $uids)
	{
		$user = null;

		foreach ($uids as $uid) {
			try {
				$user = sspmod_perun_RpcConnector::get('usersManager', 'getUserByExtSourceNameAndExtLogin', array(
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
			$member = sspmod_perun_RpcConnector::get('membersManager', 'getMemberByUser', array(
				'vo' => $vo->getId(),
				'user' => $user->getId(),
			));
		

			$memberGroups = sspmod_perun_RpcConnector::get('groupsManager', 'getAllMemberGroups', array(
				'member' => $member['id'],
			));
		} catch (sspmod_perun_Exception $e) {
                        return array();
                }

		$convertedGroups = array();
		foreach ($memberGroups as $group) {
			array_push($convertedGroups, new sspmod_perun_model_Group($group['id'], $group['name'], $group['description']));
		}

		return $convertedGroups;
	}


	public function getSpGroups($spEntityId, $vo)
	{
		$resources = sspmod_perun_RpcConnector::get('resourcesManager', 'getResources', array(
			'vo' => $vo->getId(),
		));

		$spFacilityIds = array();
		$spResources = array();
		foreach ($resources as $resource) {
			if (!array_key_exists($resource['facilityId'], $spFacilityIds)) {
				$attribute = sspmod_perun_RpcConnector::get('attributesManager', 'getAttribute', array(
					'facility' => $resource['facilityId'],
					'attributeName' => 'urn:perun:facility:attribute-def:def:entityID',
				));
				if ($attribute['value'] === $spEntityId) {
					$spFacilityIds[$resource['facilityId']] = true;
				} else {
					$spFacilityIds[$resource['facilityId']] = false;
				}
			}
			if ($spFacilityIds[$resource['facilityId']]) {
				array_push($spResources, $resource);
			}
		}

		$spGroups = array();
		foreach ($spResources as $spResource) {
			$groups = sspmod_perun_RpcConnector::get('resourcesManager', 'getAssignedGroups', array(
				'resource' => $spResource['id'],
			));
			$convertedGroups = array();
			foreach ($groups as $group) {
				array_push($convertedGroups, new sspmod_perun_model_Group($group['id'], $group['name'], $group['description']));
			}
			$spGroups = array_merge($spGroups, $convertedGroups);
		}

		$spGroups = $this->removeDuplicateEntities($spGroups);

		return $spGroups;
	}


	public function getGroupByName($vo, $name)
	{
		$group = sspmod_perun_RpcConnector::get('groupsManager', 'getGroupByName', array(
			'vo' => $vo->getId(),
			'name' => $name,
		));

		return new sspmod_perun_model_Group($group['id'], $group['name'], $group['description']);
	}


	public function getVoByShortName($voShortName)
	{
		$vo = sspmod_perun_RpcConnector::get('vosManager', 'getVoByShortName', array(
			'shortName' => $voShortName,
		));

		return new sspmod_perun_model_Vo($vo['id'], $vo['name'], $vo['shortName']);
	}


	public function getUserAttributes($user, $attrNames)
	{
		$perunAttrs = sspmod_perun_RpcConnector::get('attributesManager', 'getAttributes', array(
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


}
