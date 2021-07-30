<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Error\Exception;
use SimpleSAML\Module\perun\model\Facility;
use SimpleSAML\Module\perun\model\Group;
use SimpleSAML\Module\perun\model\HasId;
use SimpleSAML\Module\perun\model\User;
use SimpleSAML\Module\perun\model\Vo;

/**
 * Interface sspmod_perun_Adapter specify interface to get information from Perun.
 */
abstract class Adapter
{
    public const RPC = 'rpc';

    public const LDAP = 'ldap';

    /**
     * @var RpcConnector | LdapConnector
     */
    protected $connector;

    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * @param string $interface code of interface. Check constants of this class.
     * @return Adapter instance of this class. note it is NOT singleton.
     * @throws Exception thrown if interface does not match any supported interface
     */
    public static function getInstance($interface)
    {
        if ($interface === self::RPC) {
            return new AdapterRpc();
        } elseif ($interface === self::LDAP) {
            return new AdapterLdap();
        }
        throw new Exception('Unknown perun interface. Hint: try ' . self::RPC . ' or ' . self::LDAP);
    }

    /**
     * @param string $idpEntityId entity id of hosted idp used as extSourceName
     * @param string $uids list of user identifiers received from remote idp used as userExtSourceLogin
     * @return User or null if not exists
     */
    abstract public function getPerunUser($idpEntityId, $uids);

    /**
     * @param model\Vo $vo
     * @param string $name group name. Note that name of group is without VO name prefix.
     * @return Group
     * @throws Exception if does not exists
     */
    abstract public function getGroupByName($vo, $name);

    /**
     * @param string $voShortName
     * @return Vo
     * @throws Exception
     */
    abstract public function getVoByShortName($voShortName);

    /**
     * @param integer $id
     * @return Vo
     * @throws Exception if does not exists
     */
    abstract public function getVoById($id);

    /**
     * @param User $user perun user
     * @param Vo $vo vo we are working with.
     * @return Group[] groups from vo which member is. Including VO members group.
     */
    abstract public function getMemberGroups($user, $vo);

    /**
     * @param string $spEntityId entity id of the sp
     * @return Group[] from vo which are assigned to all facilities with spEntityId.
     * registering to those groups should should allow access to the service
     */
    abstract public function getSpGroups($spEntityId);

    /**
     * @param User $user
     * @param array $attrNames .
     * @return array of attribute name -> attribute
     */
    abstract public function getUserAttributes($user, $attrNames);

    /**
     * @param User $user
     * @param array $attributes of internal attribute names
     * @return array of attribute name -> attribute value
     */
    abstract public function getUserAttributesValues($user, $attributes);

    /**
     * @param string $attrName
     * @return map of all entityless attributes with attrName (for all namespaces of same attribute).
     */
    abstract public function getEntitylessAttribute($attrName);

    /**
     * @param Vo $vo
     * @param array $attrNames
     * @return array of attribute name -> attribute
     */
    abstract public function getVoAttributes($vo, $attrNames);

    /**
     * @param Vo $vo
     * @param array $attributes of internal attribute names
     * @return array of attribute name -> attribute value
     */
    abstract public function getVoAttributesValues($vo, $attributes);

    /**
     * @param Facility $facility
     * @param string $attrName
     * @return array with attribute value
     */
    abstract public function getFacilityAttribute($facility, $attrName);

    /**
     * @param string $spEntityId
     * @return Facility facility
     */
    abstract public function getFacilityByEntityId($spEntityId);

    /**
     * @param string $spEntityId entity id of the sp
     * @param int $userId
     * @return Group[] from vo which are assigned to all facilities with spEntityId for this userId
     */
    abstract public function getUsersGroupsOnFacility($spEntityId, $userId);

    /**
     * @param <String, String> map $attribute
     * @return array of Facility
     */
    abstract public function searchFacilitiesByAttributeValue($attribute);

    /**
     * @param Facility $facility
     * @param array $attrNames string $attrNames
     * @return array of attribute name -> attribute
     */
    abstract public function getFacilityAttributes($facility, $attrNames);

    /**
     * @param Facility $facility
     * @param array $attributes of internal attribute names
     * @return array of attribute name -> attribute value
     */
    abstract public function getFacilityAttributesValues($facility, $attributes);

    /**
     * @param string $extSourceName name of ext source
     * @param string $extSourceLogin login
     * @return array user ext source
     */
    abstract public function getUserExtSource($extSourceName, $extSourceLogin);

    /**
     * @param array $userExtSource ext source
     */
    abstract public function updateUserExtSourceLastAccess($userExtSource);

    /**
     * @param int $userExtSourceId userExtSourceId
     * @param array $attributes attributes
     * @return array attributes
     */
    abstract public function getUserExtSourceAttributes($userExtSourceId, $attributes);

    /**
     * @param int $userExtSourceId userExtSourceId
     * @param array $attributes attributes
     */
    abstract public function setUserExtSourceAttributes($userExtSourceId, $attributes);

    /**
     * @param sspmod_perun_model_User $user user
     * @param sspmod_perun_model_Vo $vo vo
     * @return string status, null if member does not exist
     */
    abstract public function getMemberStatusByUserAndVo($user, $vo);

    /**
     * @param User $user
     * @param string $voShortName
     * @return boolean
     */
    abstract public function isUserInVo($user, $voShortName);

    /**
     * @param int $entityId entityId
     * @param array $userGroups of groups where user belongs to
     * @return array of resource capabilities
     */
    abstract public function getResourceCapabilities($entityId, $userGroups);

    /**
     * @param int $entityId entityId
     * @return array of facility capabilities
     */
    abstract public function getFacilityCapabilities($entityId);

    /**
     * @param HasId[] $entities
     * @return HasId[] without duplicates
     */
    protected function removeDuplicateEntities($entities)
    {
        $removed = [];
        $ids = [];
        foreach ($entities as $entity) {
            if (! in_array($entity->getId(), $ids, true)) {
                array_push($ids, $entity->getId());
                array_push($removed, $entity);
            }
        }
        return $removed;
    }
}
