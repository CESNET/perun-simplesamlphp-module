<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\AdapterLdap;
use SimpleSAML\Module\perun\AdapterRpc;
use SimpleSAML\Auth\State;
use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Module\perun\model\Member;
use SimpleSAML\Module\perun\model\Group;
use SimpleSAML\Module\perun\model\Vo;
use SimpleSAML\Module\perun\model\User;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * Class PerunIdentity
 *
 * This module connects to Perun and search for user by userExtSourceLogin. If the user does not exists in Perun
 * or he is not in group assigned to service provider it redirects him to registration configurable by Perun.
 * It adds callback query parameter where user can be redirected after successfull registration of his identity
 * and try process again. Also it adds 'vo' and 'group' query parameter to let registrar know where
 * user should be registered.
 *
 * If user exists it fills 'perun' to request structure containing 'userId' and 'groups' fields.
 * User is not allowed to pass this filter until he registers and is in proper group and 'perun'
 * structure is filled properly.
 *
 * It is supposed to be used in IdP context because it needs to know entityId of destination SP from request.
 * Means it should be placed e.g. in idp-hosted metadata.
 *
 * It relays on RetainIdPEntityID filter. Config it properly before this filter. (in SP context)
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class PerunIdentity extends \SimpleSAML\Auth\ProcessingFilter
{
    const UIDS_ATTR = 'uidsAttr';
    const VO_SHORTNAME = 'voShortName';
    const REGISTER_URL_BASE = 'registerUrlBase';
    const REGISTER_URL = 'registerUrl';
    const TARGET_NEW = 'targetnew';
    const TARGET_EXISTING = 'targetexisting';
    const TARGET_EXTENDED = 'targetextended';
    const INTERFACE_PROPNAME = 'interface';
    const SOURCE_IDP_ENTITY_ID_ATTR = 'sourceIdPEntityIDAttr';
    const FORCE_REGISTRATION_TO_GROUPS = 'forceRegistrationToGroups';
    const CHECK_GROUP_MEMBERSHIP = 'checkGroupMembership';
    const ALLOW_REGISTRATION_TO_GROUPS = 'allowRegistrationToGroups';
    const PERUN_FACILITY_CHECK_GROUP_MEMBERSHIP_ATTR = 'facilityCheckGroupMembershipAttr';
    const PERUN_FACILITY_VO_SHORT_NAMES_ATTR = 'facilityVoShortNamesAttr';
    const PERUN_FACILITY_DYNAMIC_REGISTRATION_ATTR = 'facilityDynamicRegistrationAttr';
    const PERUN_FACILITY_REGISTER_URL_ATTR = 'facilityRegisterUrlAttr';
    const PERUN_FACILITY_ALLOW_REGISTRATION_TO_GROUPS = 'facilityAllowRegistrationToGroups';
    const LIST_OF_SPS_WITHOUT_INFO_ABOUT_REDIRECTION = 'listOfSpsWithoutInfoAboutRedirection';

    private $uidsAttr;
    private $registerUrlBase;
    private $registerUrl = null;
    private $defaultRegisterUrl;
    private $voShortName;
    private $facilityVoShortNames = [];
    private $listOfSpsWithoutInfoAboutRedirection = [];
    private $spEntityId;
    private $interface;
    private $checkGroupMembership = false;
    private $forceRegistrationToGroups = false;
    private $allowRegistrationToGroups;
    private $dynamicRegistration;
    private $sourceIdPEntityIDAttr;
    private $facilityCheckGroupMembershipAttr;
    private $facilityDynamicRegistrationAttr;
    private $facilityVoShortNamesAttr;
    private $facilityRegisterUrlAttr;
    private $facilityAllowRegistrationToGroupsAttr;

    /**
     * @var Adapter
     */
    private $adapter;


    /**
     * @var AdapterRpc
     */
    private $rpcAdapter;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (!isset($config[self::UIDS_ATTR])) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" . self::UIDS_ATTR . "'."
            );
        }
        if (!isset($config[self::REGISTER_URL_BASE])) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" . self::REGISTER_URL_BASE . "'."
            );
        }
        if (!isset($config[self::REGISTER_URL])) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" . self::REGISTER_URL . "'."
            );
        }
        if (!isset($config[self::VO_SHORTNAME])) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" . self::VO_SHORTNAME . "'."
            );
        }
        if (!isset($config[self::PERUN_FACILITY_CHECK_GROUP_MEMBERSHIP_ATTR])) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" .
                self::PERUN_FACILITY_CHECK_GROUP_MEMBERSHIP_ATTR . "'."
            );
        }
        if (!isset($config[self::PERUN_FACILITY_DYNAMIC_REGISTRATION_ATTR])) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" .
                self::PERUN_FACILITY_DYNAMIC_REGISTRATION_ATTR . "'."
            );
        }
        if (!isset($config[self::PERUN_FACILITY_VO_SHORT_NAMES_ATTR])) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" .
                self::PERUN_FACILITY_VO_SHORT_NAMES_ATTR . "'."
            );
        }
        if (!isset($config[self::PERUN_FACILITY_REGISTER_URL_ATTR])) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" . self::PERUN_FACILITY_REGISTER_URL_ATTR . "'."
            );
        }
        if (!isset($config[self::PERUN_FACILITY_ALLOW_REGISTRATION_TO_GROUPS])) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" .
                self::PERUN_FACILITY_ALLOW_REGISTRATION_TO_GROUPS . "'."
            );
        }
        if (!isset($config[self::INTERFACE_PROPNAME])) {
            $config[self::INTERFACE_PROPNAME] = Adapter::RPC;
        }
        if (!isset($config[self::SOURCE_IDP_ENTITY_ID_ATTR])) {
            $config[self::SOURCE_IDP_ENTITY_ID_ATTR] = RetainIdPEntityID::DEFAULT_ATTR_NAME;
        }
        if (!isset($config[self::FORCE_REGISTRATION_TO_GROUPS])) {
            $config[self::FORCE_REGISTRATION_TO_GROUPS] = false;
        }
        if (isset($config[self::LIST_OF_SPS_WITHOUT_INFO_ABOUT_REDIRECTION]) &&
            is_array($config[self::LIST_OF_SPS_WITHOUT_INFO_ABOUT_REDIRECTION])) {
            $this->listOfSpsWithoutInfoAboutRedirection = $config[self::LIST_OF_SPS_WITHOUT_INFO_ABOUT_REDIRECTION];
        }
        $this->uidsAttr = $config[self::UIDS_ATTR];
        $this->registerUrlBase = (string)$config[self::REGISTER_URL_BASE];
        $this->defaultRegisterUrl = (string)$config[self::REGISTER_URL];
        $this->voShortName = $config[self::VO_SHORTNAME];
        $this->interface = (string)$config[self::INTERFACE_PROPNAME];
        $this->sourceIdPEntityIDAttr = $config[self::SOURCE_IDP_ENTITY_ID_ATTR];
        $this->forceRegistrationToGroups = $config[self::FORCE_REGISTRATION_TO_GROUPS];
        $this->facilityCheckGroupMembershipAttr = (string)$config[self::PERUN_FACILITY_CHECK_GROUP_MEMBERSHIP_ATTR];
        $this->facilityDynamicRegistrationAttr = (string)$config[self::PERUN_FACILITY_DYNAMIC_REGISTRATION_ATTR];
        $this->facilityVoShortNamesAttr = (string)$config[self::PERUN_FACILITY_VO_SHORT_NAMES_ATTR];
        $this->facilityRegisterUrlAttr = (string)$config[self::PERUN_FACILITY_REGISTER_URL_ATTR];
        $this->facilityAllowRegistrationToGroupsAttr =
            (string) $config[self::PERUN_FACILITY_ALLOW_REGISTRATION_TO_GROUPS];
        $this->adapter = Adapter::getInstance($this->interface);
        $this->rpcAdapter = new AdapterRpc();
    }

    public function process(&$request)
    {
        assert('is_array($request)');

        # Store all user ids in an array
        $uids = [];

        foreach ($this->uidsAttr as $uidAttr) {
            if (isset($request['Attributes'][$uidAttr][0])) {
                array_push($uids, $request['Attributes'][$uidAttr][0]);
            }
        }
        if (empty($uids)) {
            throw new Exception("perun:PerunIdentity: " .
                "missing one of the mandatory attribute " . implode(', ', $this->uidsAttr) . " in request.");
        }

        if (isset($request['Attributes'][$this->sourceIdPEntityIDAttr][0])) {
            $idpEntityId = $request['Attributes'][$this->sourceIdPEntityIDAttr][0];
        } else {
            throw new Exception("perun:PerunIdentity: Cannot find entityID of source IDP. " .
                "hint: Did you properly configured RetainIdPEntityID filter in SP context?");
        }

        if (isset($request['SPMetadata']['entityid'])) {
            $this->spEntityId = $request['SPMetadata']['entityid'];
        } else {
            throw new Exception("perun:PerunIdentity: Cannot find entityID of remote SP. " .
                "hint: Do you have this filter in IdP context?");
        }

        # SP can have its own register URL
        if (isset($request['SPMetadata'][self::REGISTER_URL])) {
            $this->registerUrl = $request['SPMetadata'][self::REGISTER_URL];
        }

        $this->getSPAttributes($this->spEntityId);

        $user = $this->adapter->getPerunUser($idpEntityId, $uids);

        $this->checkMemberStateDefaultVo($request, $user, $uids);

        $groups = $this->adapter->getUsersGroupsOnFacility($this->spEntityId, $user->getId());

        if ($this->checkGroupMembership && empty($groups)) {
            if ($this->allowRegistrationToGroups) {
                $vosForRegistration = $this->getVosForRegistration($user);

                if (empty($vosForRegistration)) {
                    Logger::warning(
                        'Perun user with name: ' . $user->getName() . ' ' .
                        'is not valid member of any assigned VO for SP with entityId: (' .
                        $this->spEntityId . ') and there are no VO for registration.'
                    );
                    $this->unauthorized($request);
                }
                $this->register($request, $vosForRegistration);
            } else {
                Logger::warning(
                    'Perun user with identity/ies: ' . implode(',', $uids) .
                    ' is not member of any assigned group for resource (' . $this->spEntityId .
                    ') and registration to groups is disabled.'
                );
                $this->unauthorized($request);
            }
        }

        Logger::info(
            'Perun user with identity/ies: ' . implode(',', $uids) .
            ' has been found and SP has sufficient rights to get info about him. ' .
            'User ' . $user->getName() . ' with id: ' . $user->getId() . ' is being set to request'
        );

        if (!isset($request['perun'])) {
            $request['perun'] = [];
        }

        $request['perun']['user'] = $user;
        $request['perun']['groups'] = $groups;
    }

    /**
     * Method for register user to Perun
     * @param $request
     * @param $vosForRegistration
     * @param string $registerUrL
     * @param bool $dynamicRegistration
     */
    public function register($request, $vosForRegistration, $registerUrL = null, $dynamicRegistration = null)
    {
        if ($registerUrL === null) {
            $registerUrL = $this->registerUrl;
        }

        if ($dynamicRegistration === null) {
            $dynamicRegistration = $this->dynamicRegistration;
        }

        $request['config'] = [
            self::UIDS_ATTR => $this->uidsAttr,
            self::REGISTER_URL => $registerUrL,
            self::REGISTER_URL_BASE => $this->registerUrlBase,
            self::INTERFACE_PROPNAME => $this->interface,
            self::SOURCE_IDP_ENTITY_ID_ATTR => $this->sourceIdPEntityIDAttr,
            self::VO_SHORTNAME => $this->voShortName,
            self::PERUN_FACILITY_ALLOW_REGISTRATION_TO_GROUPS => $this->facilityAllowRegistrationToGroupsAttr,
            self::PERUN_FACILITY_CHECK_GROUP_MEMBERSHIP_ATTR => $this->facilityCheckGroupMembershipAttr,
            self::PERUN_FACILITY_DYNAMIC_REGISTRATION_ATTR => $this->facilityDynamicRegistrationAttr,
            self::PERUN_FACILITY_REGISTER_URL_ATTR => $this->facilityRegisterUrlAttr,
            self::PERUN_FACILITY_VO_SHORT_NAMES_ATTR => $this->facilityVoShortNamesAttr,
        ];

        $stateId = State::saveState($request, 'perun:PerunIdentity');
        $callback = Module::getModuleURL('perun/perun_identity_callback.php', ['stateId' => $stateId]);

        if ($dynamicRegistration) {
            $this->registerChooseVoAndGroup($callback, $vosForRegistration, $request);
        } else {
            $this->registerDirectly($request, $callback, $registerUrL);
        }
    }

    /**
     * Redirect user to registerUrL
     * @param $request
     * @param string $callback
     * @param string $registerUrL
     * @param Vo|null $vo
     * @param Group|null $group
     */
    protected function registerDirectly($request, $callback, $registerUrL, $vo = null, $group = null)
    {
        $params = [];
        if ($vo !== null) {
            $params['vo'] = $vo->getShortName();
            if ($group !== null) {
                $params['group'] = $group->getName();
            }
        }
        $params[self::TARGET_NEW] = $callback;
        $params[self::TARGET_EXISTING] = $callback;
        $params[self::TARGET_EXTENDED] = $callback;

        $id = State::saveState($request, 'perun:PerunIdentity');

        if (in_array($this->spEntityId, $this->listOfSpsWithoutInfoAboutRedirection)) {
            HTTP::redirectTrustedURL($registerUrL, $params);
        }

        $url = Module::getModuleURL('perun/unauthorized_access_go_to_registration.php');
        HTTP::redirectTrustedURL(
            $url,
            [
                'StateId' => $id,
                'SPMetadata' => $request['SPMetadata'],
                'registerUrL' => $registerUrL,
                'params' => $params
            ]
        );
    }

    /**
     * Redirect user to page with selection Vo and Group for registration
     * @param string $callback
     * @param $vosForRegistration
     * @param $request
     */
    protected function registerChooseVoAndGroup($callback, $vosForRegistration, $request)
    {

        $vosId = [];
        $chooseGroupUrl = Module::getModuleURL('perun/perun_identity_choose_vo_and_group.php');

        $stateId = State::saveState($request, 'perun:PerunIdentity');

        foreach ($vosForRegistration as $vo) {
            array_push($vosId, $vo->getId());
        }

        HTTP::redirectTrustedURL(
            $chooseGroupUrl,
            [
                self::REGISTER_URL_BASE => $this->registerUrlBase,
                'spEntityId' => $this->spEntityId,
                'vosIdForRegistration' => $vosId,
                self::INTERFACE_PROPNAME => $this->interface,
                'callbackUrl' => $callback,
                'SPMetadata' => $request['SPMetadata'],
                'stateId' => $stateId
            ]
        );
    }

    /**
     * Returns true, if entities contains VO members group
     *
     * @param Group[] $entities
     * @return bool
     */
    private function containsMembersGroup($entities)
    {
        if (empty($entities)) {
            return false;
        }
        foreach ($entities as $entity) {
            if (preg_match('/[^:]*:members$/', $entity->getName())) {
                return true;
            }
        }
        return false;
    }

    /**
     * When the process logic determines that the user is not
     * authorized for this service, then forward the user to
     * an 403 unauthorized page.
     *
     * Separated this code into its own method so that child
     * classes can override it and change the action. Forward
     * thinking in case a "chained" ACL is needed, more complex
     * permission logic.
     *
     * @param array $request
     */
    public static function unauthorized($request)
    {
        $id = State::saveState($request, 'perunauthorize:Perunauthorize');
        $url = Module::getModuleURL('perunauthorize/perunauthorize_403.php');
        if (isset($request['SPMetadata']['InformationURL']['en'])) {
            HTTP::redirectTrustedURL(
                $url,
                [
                    'StateId' => $id,
                    'informationURL' => $request['SPMetadata']['InformationURL']['en'],
                    'administrationContact' => $request['SPMetadata']['administrationContact'],
                    'serviceName' => $request['SPMetadata']['name']['en']
                ]
            );
        } else {
            HTTP::redirectTrustedURL(
                $url,
                [
                    'StateId' => $id,
                    'administrationContact' => $request['SPMetadata']['administrationContact'],
                    'serviceName' => $request['SPMetadata']['name']['en']
                ]
            );
        }
    }

    /**
     * This functions get attributes for facility
     * @param string $spEntityID
     */
    protected function getSPAttributes($spEntityID)
    {
        try {
            $facilities = $this->rpcAdapter->getFacilitiesByEntityId($spEntityID);
            if (empty($facilities)) {
                Logger::warning(
                    "perun:PerunIdentity: No facility with entityID '" . $spEntityID . "' found."
                );
                return;
            }

            $checkGroupMembership = $this->rpcAdapter->getFacilityAttribute(
                $facilities[0],
                $this->facilityCheckGroupMembershipAttr
            );
            if ($checkGroupMembership !== null) {
                $this->checkGroupMembership = $checkGroupMembership;
            }

            $facilityVoShortNames = $this->rpcAdapter->getFacilityAttribute(
                $facilities[0],
                $this->facilityVoShortNamesAttr
            );
            if (!empty($facilityVoShortNames)) {
                $this->facilityVoShortNames = $facilityVoShortNames;
            }

            $dynamicRegistration = $this->rpcAdapter->getFacilityAttribute(
                $facilities[0],
                $this->facilityDynamicRegistrationAttr
            );
            if ($dynamicRegistration !== null) {
                $this->dynamicRegistration = $dynamicRegistration;
            }

            $this->registerUrl = $this->rpcAdapter->getFacilityAttribute(
                $facilities[0],
                $this->facilityRegisterUrlAttr
            );
            if ($this->registerUrl === null) {
                $this->registerUrl = $this->defaultRegisterUrl;
            }

            $allowRegistartionToGroups = $this->rpcAdapter->getFacilityAttribute(
                $facilities[0],
                $this->facilityAllowRegistrationToGroupsAttr
            );
            if ($allowRegistartionToGroups !== null) {
                $this->allowRegistrationToGroups = $allowRegistartionToGroups;
            }
        } catch (\Exception $ex) {
            Logger::warning("perun:PerunIdentity: " . $ex);
        }
    }

    /**
     * @param $request
     * @param User $user
     * @param $uids
     */
    protected function checkMemberStateDefaultVo($request, $user, $uids)
    {
        $status = null;
        try {
            $vo = $this->adapter->getVoByShortName($this->voShortName);
            if ($user !== null) {
                $status = $this->adapter->getMemberStatusByUserAndVo($user, $vo);
            }
        } catch (\Exception $ex) {
            throw new Exception('perun:PerunIdentity: ' . $ex);
        }

        if ($vo === null) {
            throw new Exception(
                'perun:PerunIdentity: Vo with short name ' . $this->voShortName . ' does not exist.'
            );
        }

        if ($this->adapter instanceof AdapterLdap && $status === Member::INVALID) {
            try {
                $status = $this->rpcAdapter->getMemberStatusByUserAndVo($user, $vo);
            } catch (\Exception $ex) {
                Logger::info(
                    'Member status for perun user with identity/ies: ' . implode(',', $uids) . ' ' .
                    'was not VALID and it is not possible to get more info (RPC is not working)'
                );
                $this->unauthorized($request);
            }
        }

        if ($user === null || $status === null || $status === Member::EXPIRED) {
            if ($user === null) {
                Logger::info(
                    'Perun user with identity/ies: ' . implode(',', $uids) . ' ' .
                    'has NOT been found. He is being redirected to register.'
                );
            } elseif ($status === null) {
                Logger::info(
                    'Perun user with identity/ies: ' . implode(',', $uids) . ' ' .
                    'is NOT member in vo with short name ' . $this->voShortName .
                    '(default VO). He is being redirected to register.'
                );
            } else {
                Logger::info(
                    'Member status for perun user with identity/ies: ' . implode(',', $uids) . ' ' .
                    'was expired. He is being redirected to register.'
                );
            }
            $this->register($request, [$vo], $this->defaultRegisterUrl, false);
        } elseif (!($status === Member::VALID)) {
            Logger::warning(
                'Member status for perun user with identity/ies: ' . implode(',', $uids) . ' ' .
                'was INVALID/SUSPENDED/DISABLED. '
            );
            $this->unauthorized($request);
        }
    }

    /**
     * Returns list of sspmod_perun_model_Vo to which the user may register
     * @param User $user
     * @return array of Vo
     */
    protected function getVosForRegistration($user)
    {
        $vos = [];
        $members = [];
        $vosIdForRegistration = [];
        $vosForRegistration = [];

        $vos = $this->getVosByFacilityVoShortNames();
        foreach ($vos as $vo) {
            Logger::debug("Vo:" . print_r($vo, true));
            try {
                $member = $this->rpcAdapter->getMemberByUser($user, $vo);
                Logger::debug("Member:" . print_r($member, true));
                array_push($members, $member);
            } catch (\Exception $exception) {
                array_push($vosForRegistration, $vo);
                Logger::warning("perun:PerunIdentity: " . $exception);
            }
        }

        foreach ($members as $member) {
            if ($member->getStatus() === Member::VALID ||
                $member->getStatus() === Member::EXPIRED) {
                array_push($vosIdForRegistration, $member->getVoId());
            }
        }

        foreach ($vos as $vo) {
            if (in_array($vo->getId(), $vosIdForRegistration)) {
                array_push($vosForRegistration, $vo);
            }
        }
        Logger::debug("VOs for registration:  " . print_r($vosForRegistration, true));
        return $vosForRegistration;
    }

    /**
     * Returns list of Vos by voShortNames from $this->facilityVoShortNames
     * @return array of Vo
     */
    protected function getVosByFacilityVoShortNames()
    {
        $vos = [];
        foreach ($this->facilityVoShortNames as $voShortName) {
            try {
                $vo = $this->adapter->getVoByShortName($voShortName);
                array_push($vos, $vo);
            } catch (\Exception $ex) {
                Logger::warning("perun:PerunIdentity: " . $ex);
            }
        }

        return $vos;
    }
}
