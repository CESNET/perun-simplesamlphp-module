<?php

/**
 * Interface sspmod_perun_Adapter
 * specify interface to get information from Perun.
 */
abstract class sspmod_perun_Adapter
{
	const RPC = 'rpc';
	const LDAP = 'ldap';

	/**
	 * @param string $interface code of interface. Check constants of this class.
	 * @return sspmod_perun_Adapter instance of this class. note it is NOT singleton.
	 * @throws SimpleSAML_Error_Exception thrown if interface does not match any supported interface
	 */
	public static function getInstance($interface) {
		if ($interface === self::RPC) {
			return new sspmod_perun_AdapterRpc();
		} else if ($interface === self::LDAP) {
			return new sspmod_perun_AdapterLdap();
		} else {
			throw new SimpleSAML_Error_Exception('Unknown perun interface. Hint: try ' . self::RPC . ' or ' . self::LDAP);
		}
	}

	/**
	 * @param string $idpEntityId entity id of hosted idp used as extSourceName
	 * @param string $uids list of user identifiers received from remote idp used as userExtSourceLogin
	 * @return sspmod_perun_model_User or null if not exists
	 */
	public abstract function getPerunUser($idpEntityId, $uids);

	/**
	 * @param sspmod_perun_model_Vo $vo
	 * @param string $name group name. Note that name of group is without VO name prefix.
	 * @return sspmod_perun_model_Group
	 * @throws SimpleSAML_Error_Exception if does not exists
	 */
	public abstract function getGroupByName($vo, $name);

	/**
	 * @param string $voShortName
	 * @return sspmod_perun_model_Vo
	 * @throws SimpleSAML_Error_Exception if does not exists
	 */
	public abstract function getVoByShortName($voShortName);

	/**
	 * @param sspmod_perun_model_User $user perun user
	 * @param sspmod_perun_model_Vo $vo vo we are working with.
	 * @return sspmod_perun_model_Group[] groups from vo which member is. Including VO members group.
	 */
	public abstract function getMemberGroups($user, $vo);

	/**
	 * @param string $spEntityId entity id of the sp
	 * @param sspmod_perun_model_Vo $vo
	 * @return sspmod_perun_model_Group[] from vo which are assigned to all facilities with spEntityId.
	 * registering to those groups should should allow access to the service
	 */
	public abstract function getSpGroups($spEntityId, $vo);

	/**
	 * @param sspmod_perun_model_User $user
	 * @param array $attrNames.
	 * @return array associative of attributes. Keys are attribute names
	 * and values are attr values (can be null, string, array, associative array)
	 */
	public abstract function getUserAttributes($user, $attrNames);


	/**
	 * @param sspmod_perun_model_HasId[] $entities
	 * @return sspmod_perun_model_HasId[] without duplicates
	 */
	protected function removeDuplicateEntities($entities) {
		$removed = array();
		$ids = array();
		foreach ($entities as $entity) {
			if (!in_array($entity->getId(), $ids)) {
				array_push($ids, $entity->getId());
				array_push($removed, $entity);
			}
		}
		return $removed;

	}
}
