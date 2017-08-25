<?php

/**
 * Class sspmod_perun_AdapterLdap
 *
 * Perun adapter which uses Perun LDAP interface
 */
class sspmod_perun_AdapterLdap extends sspmod_perun_Adapter
{


	public function getPerunUser($idpEntityId, $uids)
	{
		# Build a LDAP query, we are searching for the user who has at least one of the uid
		$query = '';
                foreach ($uids as $uid) {
			$query .= "(eduPersonPrincipalNames=$uid)";
		}

		if (empty($query)) {
			return null;
		}

		$user = sspmod_perun_LdapConnector::searchForEntity("ou=People,dc=perun,dc=cesnet,dc=cz",
			"(|$query)",
			array("perunUserId", "displayName", "cn", "givenName", "sn", "preferredMail", "mail")
		);

		if (is_null($user)) {
			return $user;
		}

		if (isset($user['displayName'][0])) {
			$name = $user['displayName'][0];
		} else if (isset($user['cn'][0])) {
			$name = $user['cn'][0];
		} else {
			$name = null;
		}
		return new sspmod_perun_model_User($user['perunUserId'][0], $name);
	}


	public function getMemberGroups($user, $vo)
	{
		$userId = $user->getId();
		$userWithMembership = sspmod_perun_LdapConnector::searchForEntity("perunUserId=$userId,ou=People,dc=perun,dc=cesnet,dc=cz",
			"(objectClass=perunUser)",
			array("perunUserId", "memberOf")
		);

		$groups = array();
		foreach ($userWithMembership['memberOf'] as $groupDn) {
			$voId = explode('=', explode(',', $groupDn)[1], 2)[1];
			if ($voId != $vo->getId()) {
				continue;
			}

			$group = sspmod_perun_LdapConnector::searchForEntity($groupDn,
				"(objectClass=perunGroup)",
				array("perunGroupId", "cn", "perunUniqueGroupName", "perunVoId", "description")
			);
			array_push($groups, new sspmod_perun_model_Group($group['perunGroupId'][0], $group['perunUniqueGroupName'][0], $group['description'][0]));
		}

		return $groups;
	}


	public function getSpGroups($spEntityId, $vo)
	{
		$resources = sspmod_perun_LdapConnector::searchForEntities("dc=perun,dc=cesnet,dc=cz",
			"(&(objectClass=perunResource)(entityID=$spEntityId))",
			array("perunResourceId", "assignedGroupId")
		);
		$voId = $vo->getId();

		$groups = array();
		foreach ($resources as $resource) {
			foreach ($resource['assignedGroupId'] as $groupId) {
				$group = sspmod_perun_LdapConnector::searchForEntity("perunGroupId=$groupId,perunVoId=$voId,dc=perun,dc=cesnet,dc=cz",
					"(objectClass=perunGroup)",
					array("perunGroupId", "cn", "perunUniqueGroupName", "perunVoId", "description")
				);
				array_push($groups, new sspmod_perun_model_Group($group['perunGroupId'][0], $group['perunUniqueGroupName'][0], $group['description'][0]));
			}
		}

		$groups = $this->removeDuplicateEntities($groups);

		return $groups;
	}


	public function getGroupByName($vo, $name)
	{
		$voId = $vo->getId();
		$group = sspmod_perun_LdapConnector::searchForEntity("perunVoId=$voId,dc=perun,dc=cesnet,dc=cz",
			"(&(objectClass=perunGroup)(perunUniqueGroupName=$name))",
			array("perunGroupId", "cn", "perunUniqueGroupName", "perunVoId", "description")
		);
		if (is_null($group)) {
			throw new SimpleSAML_Error_Exception("Group with name: $name in VO: ".$vo->getName()." does not exists in Perun LDAP.");
		}
		$groupName = substr($group['perunUniqueGroupName'][0], strlen($vo->getShortName().':'));
		return new sspmod_perun_model_Group($group['perunGroupId'][0], $groupName, $group['description'][0]);
	}


	public function getVoByShortName($voShortName)
	{
		$vo = sspmod_perun_LdapConnector::searchForEntity("dc=perun,dc=cesnet,dc=cz",
			"(&(objectClass=perunVo)(o=$voShortName))",
			array("perunVoId", "o", "description")
		);
		if (is_null($vo)) {
			throw new SimpleSAML_Error_Exception("Vo with name: $vo does not exists in Perun LDAP.");
		}

		return new sspmod_perun_model_Vo($vo['perunVoId'][0], $vo['description'][0], $vo['o'][0]);
	}


	public function getUserAttributes($user, $attrNames)
	{
		$userId = $user->getId();
		$attributes = sspmod_perun_LdapConnector::searchForEntity("perunUserId=$userId,ou=People,dc=perun,dc=cesnet,dc=cz",
			"(objectClass=perunUser)",
			$attrNames
		);
		// user in ldap (simplified by LdapConnector method) is actually set of its attributes
		return $attributes;
	}

}
