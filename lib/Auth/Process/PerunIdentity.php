<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\AdapterLdap;
use SimpleSAML\Module\perun\AdapterRpc;
use SimpleSAML\Module\perun\model\Group;
use SimpleSAML\Module\perun\model\Member;
use SimpleSAML\Module\perun\model\User;
use SimpleSAML\Module\perun\model\Vo;
use SimpleSAML\Utils\HTTP;

/**
 * Class PerunIdentity
 *
 * This module connects to Perun and search for user by userExtSourceLogin. If the user does not exists in Perun or he
 * is not in group assigned to service provider it redirects him to registration configurable by Perun. It adds callback
 * query parameter where user can be redirected after successfull registration of his identity and try process again.
 * Also it adds 'vo' and 'group' query parameter to let registrar know where user should be registered.
 *
 * If user exists it fills 'perun' to request structure containing 'userId' and 'groups' fields. User is not allowed to
 * pass this filter until he registers and is in proper group and 'perun' structure is filled properly.
 *
 * It is supposed to be used in IdP context because it needs to know entityId of destination SP from request. Means it
 * should be placed e.g. in idp-hosted metadata.
 *
 * It relays on RetainIdPEntityID filter. Config it properly before this filter. (in SP context)
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class PerunIdentity extends \SimpleSAML\Auth\ProcessingFilter
{
    public const UIDS_ATTR = 'uidsAttr';

    public const VO_SHORTNAME = 'voShortName';

    public const REGISTER_URL_BASE = 'registerUrlBase';

    public const REGISTER_URL = 'registerUrl';

    public const TARGET_NEW = 'targetnew';

    public const TARGET_EXISTING = 'targetexisting';

    public const TARGET_EXTENDED = 'targetextended';

    public const INTERFACE_PROPNAME = 'interface';

    public const SOURCE_IDP_ENTITY_ID_ATTR = 'sourceIdPEntityIDAttr';

    public const PERUN_FACILITY_CHECK_GROUP_MEMBERSHIP_ATTR = 'facilityCheckGroupMembershipAttr';

    public const PERUN_FACILITY_VO_SHORT_NAMES_ATTR = 'facilityVoShortNamesAttr';

    public const PERUN_FACILITY_DYNAMIC_REGISTRATION_ATTR = 'facilityDynamicRegistrationAttr';

    public const PERUN_FACILITY_REGISTER_URL_ATTR = 'facilityRegisterUrlAttr';

    public const PERUN_FACILITY_ALLOW_REGISTRATION_TO_GROUPS = 'facilityAllowRegistrationToGroups';

    public const LIST_OF_SPS_WITHOUT_INFO_ABOUT_REDIRECTION = 'listOfSpsWithoutInfoAboutRedirection';

    public const MODE = 'mode';

    public const MODE_FULL = 'FULL';

    public const MODE_USERONLY = 'USERONLY';

    public const MODES = [self::MODE_FULL, self::MODE_USERONLY];

    private $uidsAttr;

    private $registerUrlBase;

    private $registerUrl;

    private $defaultRegisterUrl;

    private $voShortName;

    private $facilityVoShortNames = [];

    private $listOfSpsWithoutInfoAboutRedirection = [];

    private $mode;

    private $spEntityId;

    private $interface;

    private $checkGroupMembership = false;

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
        $config = Configuration::loadFromArray($config);

        $this->uidsAttr = $config->getArray(self::UIDS_ATTR, []);
        $this->registerUrlBase = $config->getString(self::REGISTER_URL_BASE, null);
        $this->defaultRegisterUrl = $config->getString(self::REGISTER_URL, null);
        $this->voShortName = $config->getString(self::VO_SHORTNAME, null);
        $this->interface = $config->getString(self::INTERFACE_PROPNAME, AdapterRpc::RPC);
        $this->sourceIdPEntityIDAttr =
            $config->getString(self::SOURCE_IDP_ENTITY_ID_ATTR, 'sourceIdPEntityID');
        $this->facilityCheckGroupMembershipAttr =
            $config->getString(self::PERUN_FACILITY_CHECK_GROUP_MEMBERSHIP_ATTR, null);
        $this->facilityDynamicRegistrationAttr =
            $config->getString(self::PERUN_FACILITY_DYNAMIC_REGISTRATION_ATTR, null);
        $this->facilityVoShortNamesAttr =
            $config->getString(self::PERUN_FACILITY_VO_SHORT_NAMES_ATTR, null);
        $this->facilityRegisterUrlAttr = $config->getString(self::PERUN_FACILITY_REGISTER_URL_ATTR, null);
        $this->facilityAllowRegistrationToGroupsAttr =
            $config->getString(self::PERUN_FACILITY_ALLOW_REGISTRATION_TO_GROUPS, null);
        $this->listOfSpsWithoutInfoAboutRedirection =
            $config->getArray(self::LIST_OF_SPS_WITHOUT_INFO_ABOUT_REDIRECTION, []);

        $this->mode = $config->getValueValidate(self::MODE, self::MODES, self::MODE_FULL);

        if ($this->uidsAttr === null) {
            throw new Exception(
                'perun:PerunIdentity: missing mandatory config option \'' . self::UIDS_ATTR . '\'.'
            );
        }
        if ($this->mode === self::MODE_FULL && empty($this->registerUrlBase)) {
            throw new Exception(
                'perun:PerunIdentity: missing mandatory config option \'' . self::REGISTER_URL_BASE . '\'.'
            );
        }
        if ($this->mode === self::MODE_FULL && empty($this->defaultRegisterUrl)) {
            throw new Exception(
                'perun:PerunIdentity: missing mandatory config option \'' . self::REGISTER_URL . '\'.'
            );
        }
        if ($this->mode === self::MODE_FULL && empty($this->voShortName)) {
            throw new Exception(
                'perun:PerunIdentity: missing mandatory config option \'' . self::VO_SHORTNAME . '\'.'
            );
        }
        if ($this->mode === self::MODE_FULL && empty($this->facilityCheckGroupMembershipAttr)) {
            throw new Exception(
                'perun:PerunIdentity: missing mandatory config option \'' .
                self::PERUN_FACILITY_CHECK_GROUP_MEMBERSHIP_ATTR . '\'.'
            );
        }
        if ($this->mode === self::MODE_FULL && empty($this->facilityDynamicRegistrationAttr)) {
            throw new Exception(
                'perun:PerunIdentity: missing mandatory config option \'' .
                self::PERUN_FACILITY_DYNAMIC_REGISTRATION_ATTR . '\'.'
            );
        }
        if ($this->mode === self::MODE_FULL && empty($this->facilityVoShortNamesAttr)) {
            throw new Exception(
                'perun:PerunIdentity: missing mandatory config option \'' .
                self::PERUN_FACILITY_VO_SHORT_NAMES_ATTR . '\'.'
            );
        }
        if ($this->mode === self::MODE_FULL && empty($this->facilityRegisterUrlAttr)) {
            throw new Exception(
                'perun:PerunIdentity: missing mandatory config option \'' .
                self::PERUN_FACILITY_REGISTER_URL_ATTR . '\'.'
            );
        }
        if ($this->mode === self::MODE_FULL && empty($this->facilityAllowRegistrationToGroupsAttr)) {
            throw new Exception(
                "perun:PerunIdentity: missing mandatory config option '" .
                self::PERUN_FACILITY_ALLOW_REGISTRATION_TO_GROUPS . "'."
            );
        }

        $this->adapter = Adapter::getInstance($this->interface);
        $this->rpcAdapter = new AdapterRpc();
    }

    public function process(&$request)
    {
        assert(is_array($request));

        # Store all user ids in an array
        $uids = [];

        foreach ($this->uidsAttr as $uidAttr) {
            if (isset($request['Attributes'][$uidAttr][0])) {
                array_push($uids, $request['Attributes'][$uidAttr][0]);
            }
        }
        if (empty($uids)) {
            throw new Exception('perun:PerunIdentity: ' .
                'missing one of the mandatory attribute ' . implode(', ', $this->uidsAttr) . ' in request.');
        }

        if (isset($request['Attributes'][$this->sourceIdPEntityIDAttr][0])) {
            $idpEntityId = $request['Attributes'][$this->sourceIdPEntityIDAttr][0];
        } else {
            throw new Exception('perun:PerunIdentity: Cannot find entityID of source IDP. ' .
                'hint: Did you properly configured RetainIdPEntityID filter in SP context?');
        }

        if (isset($request['SPMetadata']['entityid'])) {
            $this->spEntityId = $request['SPMetadata']['entityid'];
        } else {
            throw new Exception('perun:PerunIdentity: Cannot find entityID of remote SP. ' .
                'hint: Do you have this filter in IdP context?');
        }

        # SP can have its own register URL
        if (isset($request['SPMetadata'][self::REGISTER_URL])) {
            $this->registerUrl = $request['SPMetadata'][self::REGISTER_URL];
        }

        $groups = [];

        $user = $this->adapter->getPerunUser($idpEntityId, $uids);

        if ($this->mode === self::MODE_FULL) {
            $this->getSPAttributes($this->spEntityId);

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
        } elseif ($this->mode === self::MODE_USERONLY) {
            if (isset($user)) {
                Logger::info(
                    'Perun user with identity/ies: ' . implode(',', $uids) .
                    ' has been found in mode USERONLY ' .
                    'User ' . $user->getName() . ' with id: ' . $user->getId() . ' is being set to request'
                );
            } else {
                Logger::info(
                    'Perun user with identity/ies: ' . implode(',', $uids) .
                    ' has not been found in mode USERONLY. Redirecting to SP.'
                );
            }
        }

        if (! isset($request['perun'])) {
            $request['perun'] = [];
        }

        $request['perun']['user'] = $user;
        $request['perun']['groups'] = $groups;
    }

    /**
     * Method for register user to Perun
     *
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
        $callback = Module::getModuleURL('perun/perun_identity_callback.php', [
            'stateId' => $stateId,
        ]);

        if ($dynamicRegistration) {
            $this->registerChooseVoAndGroup($callback, $vosForRegistration, $request);
        } else {
            $this->registerDirectly($request, $callback, $registerUrL);
        }
    }

    /**
     * When the process logic determines that the user is not authorized for this service, then forward the user to an
     * 403 unauthorized page.
     *
     * Separated this code into its own method so that child classes can override it and change the action. Forward
     * thinking in case a "chained" ACL is needed, more complex permission logic.
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
                    'serviceName' => $request['SPMetadata']['name']['en'],
                ]
            );
        } else {
            HTTP::redirectTrustedURL(
                $url,
                [
                    'StateId' => $id,
                    'administrationContact' => $request['SPMetadata']['administrationContact'],
                    'serviceName' => $request['SPMetadata']['name']['en'],
                ]
            );
        }
    }

    /**
     * Redirect user to registerUrL
     *
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

        if (in_array($this->spEntityId, $this->listOfSpsWithoutInfoAboutRedirection, true)) {
            HTTP::redirectTrustedURL($registerUrL, $params);
        }

        $url = Module::getModuleURL('perun/unauthorized_access_go_to_registration.php');
        HTTP::redirectTrustedURL(
            $url,
            [
                'StateId' => $id,
                'SPMetadata' => $request['SPMetadata'],
                'registerUrL' => $registerUrL,
                'params' => $params,
            ]
        );
    }

    /**
     * Redirect user to page with selection Vo and Group for registration
     *
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
                'stateId' => $stateId,
            ]
        );
    }

    /**
     * This functions get attributes for facility
     *
     * @param string $spEntityID
     */
    protected function getSPAttributes($spEntityID)
    {
        $attributes = [
            $this->facilityCheckGroupMembershipAttr,
            $this->facilityVoShortNamesAttr,
            $this->facilityDynamicRegistrationAttr,
            $this->facilityRegisterUrlAttr,
            $this->facilityAllowRegistrationToGroupsAttr,
        ];

        try {
            $facility = $this->adapter->getFacilityByEntityId($spEntityID);

            if ($facility === null) {
                return;
            }

            $facilityAttrValues = $this->adapter->getFacilityAttributesValues($facility, $attributes);

            if (array_key_exists($this->facilityCheckGroupMembershipAttr, $facilityAttrValues)) {
                $this->checkGroupMembership = $facilityAttrValues[(string) $this->facilityCheckGroupMembershipAttr];
            }

            if (array_key_exists($this->facilityVoShortNamesAttr, $facilityAttrValues) &&
                ! empty($facilityAttrValues[(string) $this->facilityVoShortNamesAttr])) {
                $this->facilityVoShortNames = $facilityAttrValues[(string) $this->facilityVoShortNamesAttr];
            }

            if (array_key_exists($this->facilityDynamicRegistrationAttr, $facilityAttrValues)) {
                $this->dynamicRegistration = $facilityAttrValues[(string) $this->facilityDynamicRegistrationAttr];
            }

            if (array_key_exists($this->facilityRegisterUrlAttr, $facilityAttrValues)) {
                $this->registerUrl = $facilityAttrValues[(string) $this->facilityRegisterUrlAttr];
            }

            if ($this->registerUrl === null) {
                $this->registerUrl = $this->defaultRegisterUrl;
            }

            if (array_key_exists($this->facilityAllowRegistrationToGroupsAttr, $facilityAttrValues)) {
                $this->allowRegistrationToGroups =
                    $facilityAttrValues[(string) $this->facilityAllowRegistrationToGroupsAttr];
            }
        } catch (\Exception $ex) {
            Logger::warning('perun:PerunIdentity: ' . $ex);
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
        } elseif (! ($status === Member::VALID)) {
            Logger::warning(
                'Member status for perun user with identity/ies: ' . implode(',', $uids) . ' ' .
                'was INVALID/SUSPENDED/DISABLED. '
            );
            $this->unauthorized($request);
        }
    }

    /**
     * Returns list of sspmod_perun_model_Vo to which the user may register
     *
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
            Logger::debug('Vo:' . json_encode($vo));
            try {
                $member = $this->rpcAdapter->getMemberByUser($user, $vo);
                Logger::debug('Member:' . json_encode($member));
                array_push($members, $member);
            } catch (\Exception $exception) {
                array_push($vosForRegistration, $vo);
                Logger::warning('perun:PerunIdentity: ' . $exception);
            }
        }

        foreach ($members as $member) {
            if ($member->getStatus() === Member::VALID ||
                $member->getStatus() === Member::EXPIRED) {
                array_push($vosIdForRegistration, $member->getVoId());
            }
        }

        foreach ($vos as $vo) {
            if (in_array($vo->getId(), $vosIdForRegistration, true)) {
                array_push($vosForRegistration, $vo);
            }
        }
        Logger::debug('VOs for registration:  ' . json_encode($vosForRegistration));
        return $vosForRegistration;
    }

    /**
     * Returns list of Vos by voShortNames from $this->facilityVoShortNames
     *
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
                Logger::warning('perun:PerunIdentity: ' . $ex);
            }
        }

        return $vos;
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
}
