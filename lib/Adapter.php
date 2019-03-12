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
     * @var sspmod_perun_RpcConnector | sspmod_perun_LdapConnector
     */
    protected $connector;

    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * @param string $interface code of interface. Check constants of this class.
     * @return sspmod_perun_Adapter instance of this class. note it is NOT singleton.
     * @throws SimpleSAML_Error_Exception thrown if interface does not match any supported interface
     */
    public static function getInstance($interface)
    {
        if ($interface === self::RPC) {
            return new sspmod_perun_AdapterRpc();
        } else {
            if ($interface === self::LDAP) {
                return new sspmod_perun_AdapterLdap();
            } else {
                throw new SimpleSAML_Error_Exception(
                    'Unknown perun interface. Hint: try ' . self::RPC . ' or ' . self::LDAP
                );
            }
        }
    }

    /**
     * @param string $idpEntityId entity id of hosted idp used as extSourceName
     * @param string $uids list of user identifiers received from remote idp used as userExtSourceLogin
     * @return sspmod_perun_model_User or null if not exists
     */
    abstract public function getPerunUser($idpEntityId, $uids);

    /**
     * @param sspmod_perun_model_Vo $vo
     * @param string $name group name. Note that name of group is without VO name prefix.
     * @return sspmod_perun_model_Group
     * @throws SimpleSAML_Error_Exception if does not exists
     */
    abstract public function getGroupByName($vo, $name);

    /**
     * @param string $voShortName
     * @return sspmod_perun_model_Vo
     * @throws SimpleSAML_Error_Exception if does not exists
     */
    abstract public function getVoByShortName($voShortName);

    /**
     * @param integer $id
     * @return sspmod_perun_model_Vo
     * @throws SimpleSAML_Error_Exception if does not exists
     */
    abstract public function getVoById($id);

    /**
     * @param sspmod_perun_model_User $user perun user
     * @param sspmod_perun_model_Vo $vo vo we are working with.
     * @return sspmod_perun_model_Group[] groups from vo which member is. Including VO members group.
     */
    abstract public function getMemberGroups($user, $vo);

    /**
     * @param string $spEntityId entity id of the sp
     * @return sspmod_perun_model_Group[] from vo which are assigned to all facilities with spEntityId.
     * registering to those groups should should allow access to the service
     */
    abstract public function getSpGroups($spEntityId);

    /**
     * @param sspmod_perun_model_User $user
     * @param array $attrNames .
     * @return array associative of attributes. Keys are attribute names
     * and values are attr values (can be null, string, array, associative array)
     */
    abstract public function getUserAttributes($user, $attrNames);

    /**
     * @param string $attrName
     * @return array of all entityless attributes with attrName (for all namespaces of same attribute).
     */
    abstract public function getEntitylessAttribute($attrName);

    /**
     * @param sspmod_perun_model_Vo $vo
     * @param array $attrNames
     * @return array associative of attributes. Keys are attribute names
     * and values are attr values (can be null, string, array, associative array)* @return
     */
    abstract public function getVoAttributes($vo, $attrNames);

    /**
     * @param sspmod_perun_model_Facility $facility
     * @param string $attrName
     * @return array with attribute value
     */
    abstract public function getFacilityAttribute($facility, $attrName);

    /**
     * @param string $spEntityId
     * @return sspmod_perun_model_Facility entities[]
     */
    abstract public function getFacilitiesByEntityId($spEntityId);

    /**
     * @param string $spEntityId entity id of the sp
     * @param int $userId
     * @return sspmod_perun_model_Group[] from vo which are assigned to all facilities with spEntityId for this userId
     */
    abstract public function getUsersGroupsOnFacility($spEntityId, $userId);

    /**
     * @param <String, String> map $attribute
     * @return array of sspmod_perun_model_Facility
     */
    abstract public function searchFacilitiesByAttributeValue($attribute);

    /**
     * @param sspmod_perun_model_Facility $facility
     * @param $attrNames array string $attrNames
     * @return array of attributes
     */
    abstract public function getFacilityAttributes($facility, $attrNames);

    /**
     * @param $extSourceName string name of ext source
     * @param $extSourceLogin string login
     * @return array user ext source
     */
    abstract public function getUserExtSource($extSourceName, $extSourceLogin);

    /**
     * @param $userExtSource array ext source
     */
    abstract public function updateUserExtSourceLastAccess($userExtSource);

    /**
     * @param $userExtSourceId int userExtSourceId
     * @param $attributes array attributes
     * @return array attributes
     */
    abstract public function getUserExtSourceAttributes($userExtSourceId, $attributes);

    /**
     * @param $userExtSourceId int userExtSourceId
     * @param $attributes array attributes
     */
    abstract public function setUserExtSourceAttributes($userExtSourceId, $attributes);

    /**
     * @param sspmod_perun_model_User $user user
     * @param sspmod_perun_model_Vo $vo vo
     * @return string status, null if member does not exist
     */
    abstract public function getMemberStatusByUserAndVo($user, $vo);

    /**
     * @param sspmod_perun_model_HasId[] $entities
     * @return sspmod_perun_model_HasId[] without duplicates
     */
    protected function removeDuplicateEntities($entities)
    {
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
