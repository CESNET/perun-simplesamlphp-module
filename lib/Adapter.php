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
     * @var RpcConnector|LdapConnector
     */
    protected $connector;

    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * @param string $interface code of interface. Check constants of this class.
     *
     * @return Adapter instance of this class. note it is NOT singleton.
     */
    public static function getInstance($interface)
    {
        if ($interface === self::RPC) {
            return new AdapterRpc();
        }
        if ($interface === self::LDAP) {
            return new AdapterLdap();
        }
        throw new Exception('Unknown perun interface. Hint: try ' . self::RPC . ' or ' . self::LDAP);
    }

    /**
     * @param string $idpEntityId entity id of hosted idp used as extSourceName
     * @param array  $uids        list of user identifiers received from remote idp used as userExtSourceLogin
     *
     * @return User or null if not exists
     */
    abstract public function getPerunUser($idpEntityId, $uids);

    /**
     * @param string $idpEntityId entity id of hosted idp used as extSourceName
     * @param array  $uids        list of user identifiers received from remote idp used as userExtSourceLogin
     *
     * @return User or null if not exists
     */
    abstract public function getPerunUserByAdditionalIdentifiers($idpEntityId, $uids);

    /**
     * @param model\Vo $vo
     * @param string   $name group name. Note that name of group is without VO name prefix.
     *
     * @return Group
     */
    abstract public function getGroupByName($vo, $name);

    /**
     * @param string $voShortName
     *
     * @return Vo
     */
    abstract public function getVoByShortName($voShortName);

    /**
     * @param int $id
     *
     * @return Vo
     */
    abstract public function getVoById($id);

    /**
     * @param User $user perun user
     * @param Vo   $vo   vo we are working with
     *
     * @return Group[] groups from vo which member is. Including VO members group.
     */
    abstract public function getMemberGroups($user, $vo);

    /**
     * @param User $user perun user
     * @param Vo   $vo   vo we are working with
     *
     * @return Group[] groups from vo where user is valid
     */
    abstract public function getGroupsWhereMemberIsActive($user, $vo);

    /**
     * @param string $spEntityId   entity id of the sp
     * @param string $entityIdAttr entity id attribute
     *
     * @return Group[] from vo which are assigned to all facilities with spEntityId.
     *                 registering to those groups should should allow access to the service
     */
    abstract public function getSpGroups(string $spEntityId, string $entityIdAttr): array;

    /**
     * @param Facility $facility representing the SP
     *
     * @return Group[] from vo which are assigned to all facilities with spEntityId.
     *                 registering to those groups should allow access to the service
     */
    abstract public function getSpGroupsByFacility(Facility $facility): array;

    /**
     * @param User  $user
     * @param array $attrNames
     *
     * @return array of attribute name -> attribute
     */
    abstract public function getUserAttributes($user, $attrNames);

    /**
     * @param User  $user
     * @param array $attributes of internal attribute names
     *
     * @return array of attribute name -> attribute value
     */
    abstract public function getUserAttributesValues($user, $attributes);

    /**
     * @param string $attrName
     *
     * @return map of all entityless attributes with attrName (for all namespaces of same attribute)
     */
    abstract public function getEntitylessAttribute($attrName);

    /**
     * @param Vo    $vo
     * @param array $attrNames
     *
     * @return array of attribute name -> attribute
     */
    abstract public function getVoAttributes($vo, $attrNames);

    /**
     * @param Vo    $vo
     * @param array $attributes of internal attribute names
     *
     * @return array of attribute name -> attribute value
     */
    abstract public function getVoAttributesValues($vo, $attributes);

    /**
     * @param Facility $facility
     * @param string   $attrName
     *
     * @return array with attribute value
     */
    abstract public function getFacilityAttribute($facility, $attrName);

    /**
     * @param string $spEntityId   Value of the entityID identifier
     * @param string $entityIdAttr entity id attribute
     *
     * @return Facility facility
     */
    abstract public function getFacilityByEntityId($spEntityId, $entityIdAttr);

    /**
     * @param string $clientId     Value of the client_id identifier
     * @param string $clientIdAttr Internal name of the client_id attribute, defaults to 'perunFacilityAttr_OIDCClientID'
     *                             this key has to be present in the attribute map configuration (see perun_attributes.php config template)
     *
     * @return Facility facility
     */
    abstract public function getFacilityByClientId($clientId, $clientIdAttr);

    /**
     * @param string $spEntityId   entity id of the sp
     * @param int    $userId
     * @param string $entityIdAttr entity id attribute
     *
     * @return Group[] from vo which are assigned to all facilities with spEntityId for this userId
     */
    abstract public function getUsersGroupsOnFacility($spEntityId, $userId, $entityIdAttr);

    /**
     * @param Facility $facility entity id of the sp
     * @param int      $userId
     *
     * @return Group[] from vo which are assigned to all facilities with spEntityId for this userId
     */
    abstract public function getUsersGroupsOnSp($facility, $userId);

    /**
     * @param <String, String> map $attribute
     *
     * @return array of Facility
     */
    abstract public function searchFacilitiesByAttributeValue($attribute);

    /**
     * @param Facility $facility
     * @param array    $attrNames string $attrNames
     *
     * @return array of attribute name -> attribute
     */
    abstract public function getFacilityAttributes($facility, $attrNames);

    /**
     * @param Facility $facility
     * @param array    $attributes of internal attribute names
     *
     * @return array of attribute name -> attribute value
     */
    abstract public function getFacilityAttributesValues($facility, $attributes);

    /**
     * @param string $extSourceName  name of ext source
     * @param string $extSourceLogin login
     *
     * @return array user ext source
     */
    abstract public function getUserExtSource($extSourceName, $extSourceLogin);

    /**
     * @param array $userExtSource ext source
     */
    abstract public function updateUserExtSourceLastAccess($userExtSource);

    /**
     * @param int   $userExtSourceId userExtSourceId
     * @param array $attributes      attributes
     *
     * @return array attributes
     */
    abstract public function getUserExtSourceAttributes($userExtSourceId, $attributes);

    /**
     * @param int   $userExtSourceId userExtSourceId
     * @param array $attributes      attributes
     */
    abstract public function setUserExtSourceAttributes($userExtSourceId, $attributes);

    /**
     * @param sspmod_perun_model_User $user user
     * @param sspmod_perun_model_Vo   $vo   vo
     *
     * @return string status, null if member does not exist
     */
    abstract public function getMemberStatusByUserAndVo($user, $vo);

    /**
     * @param User   $user
     * @param string $voShortName
     *
     * @return bool
     */
    abstract public function isUserInVo($user, $voShortName);

    /**
     * @param string $spEntityId   entityId
     * @param array $userGroups    of groups where user belongs to
     * @param string $entityIdAttr entity id attribute
     *
     * @return array of resource capabilities
     */
    abstract public function getResourceCapabilities(
        string $spEntityId,
        array $userGroups,
        string $entityIdAttr
    ): array;

    /**
     * @param string $spEntityId   entityId
     * @param string $entityIdAttr entity id attribute
     *
     * @return array of facility capabilities
     */
    abstract public function getFacilityCapabilities(string $spEntityId, string $entityIdAttr): array;

    /**
     * @param HasId[] $entities
     *
     * @return HasId[] without duplicates
     */
    protected function removeDuplicateEntities($entities)
    {
        $removed = [];
        $ids = [];
        foreach ($entities as $entity) {
            if (!in_array($entity->getId(), $ids, true)) {
                array_push($ids, $entity->getId());
                array_push($removed, $entity);
            }
        }

        return $removed;
    }
}
