<?php

/**
 * Class sspmod_perun_AdapterLdap
 *
 * Configuration file should be placed in default config folder of SimpleSAMLphp.
 * Example of file is in config-template folder.
 *
 * Perun adapter which uses Perun LDAP interface
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class sspmod_perun_AdapterLdap extends sspmod_perun_Adapter
{
	const DEFAULT_CONFIG_FILE_NAME = 'module_perun.php';
	const LDAP_HOSTNAME = 'ldap.hostname';
	const LDAP_USER = 'ldap.username';
	const LDAP_PASSWORD = 'ldap.password';
	const LDAP_BASE  = 'ldap.base';

	private $ldapHostname;
	private $ldapUser;
	private $ldapPassword;
	private $ldapBase;

	protected $connector;

	public function __construct ($configFileName = null)
	{
		if (is_null($configFileName)) {
			$configFileName = self::DEFAULT_CONFIG_FILE_NAME;
		}

		$conf = SimpleSAML_Configuration::getConfig($configFileName);

		$this->ldapHostname = $conf->getString(self::LDAP_HOSTNAME);
		$this->ldapUser = $conf->getString(self::LDAP_USER);
		$this->ldapPassword = $conf->getString(self::LDAP_PASSWORD);
		$this->ldapBase = $conf->getString(self::LDAP_BASE);


		$this->connector = new sspmod_perun_LdapConnector($this->ldapHostname, $this->ldapUser, $this->ldapPassword);
	}

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

		$user = $this->connector->searchForEntity("ou=People," . $this->ldapBase,
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
		$userWithMembership = $this->connector->searchForEntity("perunUserId=$userId,ou=People," . $this->ldapBase,
			"(objectClass=perunUser)",
			array("perunUserId", "memberOf")
		);

		$groups = array();
		foreach ($userWithMembership['memberOf'] as $groupDn) {
			$voId = explode('=', explode(',', $groupDn)[1], 2)[1];
			if ($voId != $vo->getId()) {
				continue;
			}

			$group = $this->connector->searchForEntity($groupDn,
				"(objectClass=perunGroup)",
				array("perunGroupId", "cn", "perunUniqueGroupName", "perunVoId", "description")
			);
			array_push($groups, new sspmod_perun_model_Group($group['perunGroupId'][0], $group['perunUniqueGroupName'][0], $group['description'][0]));
		}

		return $groups;
	}


	public function getSpGroups($spEntityId, $vo)
	{
		$resources = $this->connector->searchForEntities($this->ldapBase,
			"(&(objectClass=perunResource)(entityID=$spEntityId))",
			array("perunResourceId", "assignedGroupId", "perunVoId")
		);

		$groups = array();
		foreach ($resources as $resource) {
			foreach ($resource['assignedGroupId'] as $groupId) {
				$group = $this->connector->searchForEntity("perunGroupId=$groupId,perunVoId=" . $resource['perunVoId'][0] . "," . $this->ldapBase,
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
		$group = $this->connector->searchForEntity("perunVoId=$voId," . $this->ldapBase,
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
		$vo = $this->connector->searchForEntity($this->ldapBase,
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
		$attributes = $this->connector->searchForEntity("perunUserId=$userId,ou=People," . $this->ldapBase,
			"(objectClass=perunUser)",
			$attrNames
		);
		// user in ldap (simplified by LdapConnector method) is actually set of its attributes
		return $attributes;
	}

	public function getFacilitiesByEntityId($spEntityId)
	{
		// TODO: Implement getEntityByEntityId() method.
	}

	public function getEntitylessAttribute($attrName)
	{
		throw new BadMethodCallException("NotImplementedException");
		// TODO: Implement getEntitylessAttribute() method.
	}

	public function getVoAttributes($vo, $attrNames)
	{
		throw new BadMethodCallException("NotImplementedException");
		// TODO: Implement getVoAttribute() method.
	}

	public function getFacilityAttribute($facility, $attrName)
	{
		throw new BadMethodCallException("NotImplementedException");
		// TODO: Implement getFacilityAttribute() method.
	}

	public function searchFacilitiesByAttributeValue($attribute)
	{
		throw new BadMethodCallException("NotImplementedException");
		// TODO: Implement searchFacilitiesByAttributeValue() method.
	}

	public function getFacilityAttributes($facility, $attrNames)
	{
		throw new BadMethodCallException("NotImplementedException");
		// TODO: Implement getFacilityAttributes() method.
	}

	public function getUsersGroupsOnFacility($spEntityId, $userId)
	{
		$resources = $this->connector->searchForEntities($this->ldapBase,
			"(&(objectClass=perunResource)(entityID=$spEntityId))",
			array("perunResourceId")
		);
		SimpleSAML\Logger::debug("Resources - ".var_export($resources, true));

		if (is_null($resources)) {
			throw new SimpleSAML_Error_Exception("Service with spEntityId: ". $spEntityId ." hasn't assigned any resource.");
		}
		$resourcesString = "(|";
		foreach ($resources as $resource){
			$resourcesString .= "(assignedToResourceId=".$resource['perunResourceId'][0].")";
		}
		$resourcesString .= ")";

		$resultGroups = array();
		$groups = $this->connector->searchForEntities($this->ldapBase,
			"(&(uniqueMember=perunUserId=".$userId.", ou=People," . $this->ldapBase. ")".$resourcesString.")",
			array("perunGroupId", "cn", "perunUniqueGroupName", "perunVoId", "description")
		);

		foreach ($groups as $group) {
			array_push($resultGroups, new sspmod_perun_model_Group($group['perunGroupId'][0], $group['perunUniqueGroupName'][0], $group['description'][0]));

		}
		$resultGroups = $this->removeDuplicateEntities($resultGroups);
		SimpleSAML\Logger::debug("Groups - ".var_export($resultGroups, true));
		return $resultGroups;
	}


}
