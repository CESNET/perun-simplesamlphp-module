<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\model\Facility;
use SimpleSAML\Module\perun\model\Group;
use SimpleSAML\Module\perun\model\Member;
use SimpleSAML\Module\perun\model\User;
use SimpleSAML\Module\perun\PerunConstants;
use SimpleSAML\Utils\HTTP;

/**
 * Class PerunIdentity.
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
 */
class SpAuthorization extends ProcessingFilter
{
    public const STAGE = 'perun:SpAuthorization';
    public const DEBUG_PREFIX = self::STAGE . ' - ';

    public const CALLBACK = 'perun/sp_authorization_callback.php';
    public const REDIRECT_NOTIFY = 'perun/sp_authorization_notify.php';
    public const TEMPLATE_NOTIFY = 'perun:sp-authorization-notify-tpl.php';
    public const REDIRECT_SELECT = 'perun/sp_authorization_select.php';
    public const TEMPLATE_SELECT = 'perun:sp-authorization-select-tpl.php';
    public const REDIRECT_403 = 'perun/sp_authorization_403.php';
    public const TEMPLATE_403 = 'perun:sp-authorization-403-tpl.php';

    public const REDIRECT_PARAMS = 'redirect_params';

    public const PARAM_STATE_ID = PerunConstants::STATE_ID;
    public const PARAM_SP_METADATA = PerunConstants::SP_METADATA;
    public const PARAM_REGISTRATION_URL = 'registrationUrl';
    public const PARAM_REGISTRATION_DATA = 'registrationData';
    public const PARAM_CALLBACK = 'callback';

    public const INTERFACE = 'interface';
    public const REGISTRAR_URL = 'registrar_url';
    public const CHECK_GROUP_MEMBERSHIP_ATTR = 'check_group_membership_attr';
    public const VO_SHORT_NAMES_ATTR = 'vo_short_names_attr';
    public const HANDLE_UNSATISFIED_MEMBERSHIP = 'handle_unsatisfied_membership';
    public const REGISTRATION_LINK_ATTR = 'registration_link_attr';
    public const ALLOW_REGISTRATION_ATTR = 'allow_registration_attr';

    public const SKIP_NOTIFICATION_SPS = 'skip_notification_sps';
    public const CHECK_GROUP_MEMBERSHIP = 'check_group_membership';
    public const VO_SHORT_NAMES = 'vo_short_names';
    public const ALLOW_REGISTRATION = 'allow_registration';
    public const REGISTRATION_LINK = 'registration_link';

    private $adapter;
    private $rpcAdapter;
    private $checkGroupMembershipAttr;
    private $voShortNamesAttr;
    private $allowRegistrationAttr;
    private $registrationLinkAttr;
    private $skipNotificationSps;
    private $handleUnsatisfiedMembership;
    private $registrarUrl;
    private $config;
    private $filterConfig;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $this->config = $config;
        $this->filterConfig = Configuration::loadFromArray($config);

        $interface = $this->filterConfig->getString(self::INTERFACE, Adapter::RPC);
        $this->adapter = Adapter::getInstance($interface);

        $this->checkGroupMembershipAttr = $this->filterConfig->getString(self::CHECK_GROUP_MEMBERSHIP_ATTR, null);
        if (empty($this->checkGroupMembershipAttr)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Invalid configuration: no attribute containing trigger option for check ' . 'has been configured. Use option \'' . self::CHECK_GROUP_MEMBERSHIP_ATTR . '\' to configure the name ' . 'of the Perun attribute, which should contain the trigger value.'
            );
        }

        $this->voShortNamesAttr = $this->filterConfig->getString(self::VO_SHORT_NAMES_ATTR, null);
        if (empty($this->voShortNamesAttr)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Invalid configuration: no attribute containing list of VO short names ' . 'has been configured. Use option \'' . self::VO_SHORT_NAMES_ATTR . '\' to configure the name ' . 'of the Perun attribute, which should contain the list of VOs for which service has resources created.'
            );
        }

        $this->registrarUrl = $this->filterConfig->getString(self::REGISTRAR_URL, null);
        $this->allowRegistrationAttr = $this->filterConfig->getString(self::ALLOW_REGISTRATION_ATTR, null);
        $this->registrationLinkAttr = $this->filterConfig->getString(self::REGISTRATION_LINK_ATTR, null);
        $this->skipNotificationSps = $this->filterConfig->getArray(self::SKIP_NOTIFICATION_SPS, []);

        $this->handleUnsatisfiedMembership = $this->filterConfig->getBoolean(self::HANDLE_UNSATISFIED_MEMBERSHIP, true);
        if ($this->handleUnsatisfiedMembership) {
            if (empty($this->registrationLinkAttr) && empty($this->registrarUrl)) {
                throw new Exception(
                    self::DEBUG_PREFIX . 'Invalid configuration: filter should handle unsatisfied membership via registration, but neither registrarUrl nor registrationLinkAttr have been configured. Use option \'' . self::REGISTRAR_URL . '\' to configure the registrar location or/and option \'' . self::REGISTRATION_LINK_ATTR . '\' to configure attribute for Service defined registration link.'
                );
            }
            try {
                $this->rpcAdapter = Adapter::getInstance(Adapter::RPC);
            } catch (Exception $ex) {
                Logger::warning(self::DEBUG_PREFIX . 'Could not create RPC adapter. Registrations will not work.');
                Logger::debug($ex);
                $this->rpcAdapter = null;
            }
        }
    }

    public function process(&$request)
    {
        assert(is_array($request));
        assert(!empty($request[PerunConstants::SP_METADATA][PerunConstants::SP_METADATA_ENTITYID]));
        assert(!empty($request[PerunConstants::PERUN][PerunConstants::USER]));

        $request[PerunConstants::CONTINUE_FILTER_CONFIG] = $this->config;

        if (empty($request[PerunConstants::SP_METADATA][PerunConstants::SP_METADATA_ENTITYID])) {
            throw new Exception(self::DEBUG_PREFIX . 'Request does not contain required SP EntityID');
        }
        $spEntityId = $request[PerunConstants::SP_METADATA][PerunConstants::SP_METADATA_ENTITYID];

        if (empty($request[PerunConstants::PERUN][PerunConstants::USER])) {
            Logger::debug(self::DEBUG_PREFIX . 'Request does not contain Perun user. Did you configure ' . PerunUser::STAGE . ' filter before this filter in the processing chain?');
            $this->unauthorized($request);
        }
        $user = $request[PerunConstants::PERUN][PerunConstants::USER];
        $facility = $this->adapter->getFacilityByEntityId($spEntityId);
        if (null === $facility) {
            Logger::debug(
                self::DEBUG_PREFIX . 'No facility found for SP \'' . $spEntityId . '\', skip processing filter'
            );
            return;
        }
        $facilityAttributes = $this->getSPAttributes($facility);
        if (empty($facilityAttributes)) {
            Logger::warning(
                self::DEBUG_PREFIX . 'Could not fetch SP attributes, user will be redirected to unauthorized for security reasons'
            );
            $this->unauthorized($request);
            return;
        }

        $checkGroupMembership = $facilityAttributes[self::CHECK_GROUP_MEMBERSHIP];
        if (!$checkGroupMembership) {
            Logger::info(self::DEBUG_PREFIX . 'Group membership check not requested by the service.');
            return;
        }

        $userGroups = $this->adapter->getUsersGroupsOnFacility($facility, $user->getId());
        if (!empty($userGroups)) {
            Logger::info(self::DEBUG_PREFIX . 'User satisfies the group membership check.');
        } else {
            $this->handleUnsatisfiedMembership($request, $user, $spEntityId, $facility, $facilityAttributes);
        }
    }

    public static function unauthorized(array $request)
    {
        $stateId = State::saveState($request, self::STAGE);
        $url = Module::getModuleURL(self::REDIRECT_403);
        HTTP::redirectTrustedURL($url, [
            self::PARAM_STATE_ID => $stateId,
        ]);
    }

    public function handleUnsatisfiedMembership(
        array $request,
        User $user,
        string $spEntityId,
        Facility $facility,
        array $facilityAttributes
    ) {
        if (!$this->handleUnsatisfiedMembership) {
            Logger::debug(self::DEBUG_PREFIX . 'Handling unsatisfied membership is disabled, redirecting to unauthorized');
            $this->unauthorized($request);
            return;
        }
        $allowRegistration = $facilityAttributes[self::ALLOW_REGISTRATION] ?? false;
        if ($allowRegistration) {
            $registrationLink = $facilityAttributes[self::REGISTRATION_LINK] ?? null;
            if (!empty($registrationLink)) {
                Logger::debug(
                    self::DEBUG_PREFIX . 'Redirecting user to custom registration link \'' . $registrationLink
                    . '\' configured for service (' . $spEntityId . ').'
                );
                $stateId = State::saveState($request, self::STAGE);
                $callback = Module::getModuleURL(self::CALLBACK, [
                    self::PARAM_STATE_ID => $stateId,
                ]);
                HTTP::redirectTrustedURL($registrationLink, [
                    self::PARAM_CALLBACK => $callback,
                ]);
                exit;
            }
            try {
                $registrationData = $this->getRegistrationData($user, $facility, $spEntityId, $facilityAttributes);
                if (!empty($registrationData)) {
                    $skipNotification = in_array($spEntityId, $this->skipNotificationSps, true);
                    $this->register($request, $registrationData, $skipNotification);
                    return;
                }
                Logger::debug(
                    self::DEBUG_PREFIX . 'No VO is available for registration into groups of resources for '
                        . 'service (' . $spEntityId . ').'
                );
            } catch (Exception $ex) {
                Logger::warning(
                    self::DEBUG_PREFIX . 'Caught exception, user will be redirected to unauthorized for security reasons'
                );
                Logger::debug($ex->getMessage());
            }
        } else {
            Logger::debug(
                self::DEBUG_PREFIX . 'User is not member of any assigned groups of resources for service ('
                . $spEntityId . '). Registration to the groups is disabled.'
            );
        }
        $this->unauthorized($request);
    }

    public function register(array $request, array $registrationData, bool $skipNotification)
    {
        $singleRegistration = 1 === count($registrationData);
        if ($singleRegistration) {
            Logger::debug(
                self::DEBUG_PREFIX . 'Registration possible to only single VO and GROUP, '
                . 'redirecting directly to this registration.'
            );
            $group = array_pop($registrationData);
            $this->registerDirectly($request, $group, $skipNotification);
        } else {
            Logger::debug(
                self::DEBUG_PREFIX . 'Registration possible to more than single VO and GROUP, '
                . 'let user choose.'
            );
            $this->registerChooseVoAndGroup($request, $registrationData);
        }
    }

    protected function getSPAttributes(Facility $facility): array
    {
        $attributes = [$this->checkGroupMembershipAttr, $this->voShortNamesAttr];

        if (!empty($this->allowRegistrationAttr)) {
            $attributes[] = $this->allowRegistrationAttr;
        }
        if (!empty($this->registrationLinkAttr)) {
            $attributes[] = $this->registrationLinkAttr;
        }

        $result = [];
        try {
            $facilityAttrValues = $this->adapter->getFacilityAttributesValues($facility, $attributes);

            if (array_key_exists($this->checkGroupMembershipAttr, $facilityAttrValues)) {
                $result[self::CHECK_GROUP_MEMBERSHIP] = (bool) $facilityAttrValues[$this->checkGroupMembershipAttr] ?? false;
            }

            if (array_key_exists($this->voShortNamesAttr, $facilityAttrValues)) {
                $result[self::VO_SHORT_NAMES] = $facilityAttrValues[$this->voShortNamesAttr] ?? [];
            }

            if (
                !empty($this->allowRegistrationAttr)
                && array_key_exists($this->allowRegistrationAttr, $facilityAttrValues)
            ) {
                $result[self::ALLOW_REGISTRATION] = (bool) $facilityAttrValues[$this->allowRegistrationAttr] ?? false;
            }

            if (
                !empty($this->registrationLinkAttr)
                && array_key_exists($this->registrationLinkAttr, $facilityAttrValues)
            ) {
                $result[self::REGISTRATION_LINK] = (string) $facilityAttrValues[$this->registrationLinkAttr];
            }
        } catch (\Exception $ex) {
            Logger::warning(
                self::DEBUG_PREFIX . 'Caught exception when fetching attributes for facility \''
                . $facility->getId() . '\''
            );
            Logger::debug($ex);
            throw $ex;
        }

        return $result;
    }

    protected function registerDirectly(array &$request, Group $group, bool $skipNotification)
    {
        $stateId = State::saveState($request, self::STAGE);
        $callback = Module::getModuleURL(self::CALLBACK, [
            self::PARAM_STATE_ID => $stateId,
        ]);

        $nameParts = explode(':', $group->getUniqueName(), 2);
        $params[PerunConstants::VO] = $nameParts[0];
        if (!empty($group) && PerunConstants::GROUP_MEMBERS !== $nameParts[1]) {
            $params[PerunConstants::GROUP] = $group;
        }
        $params[PerunConstants::TARGET_NEW] = $callback;
        $params[PerunConstants::TARGET_EXISTING] = $callback;
        $params[PerunConstants::TARGET_EXTENDED] = $callback;

        $registrationUrl = HTTP::addURLParameters($this->registrarUrl, $params);
        if ($skipNotification) {
            Logger::debug(
                self::DEBUG_PREFIX . 'Skipping registration notification. Redirecting directly to \''
                . $registrationUrl . '\'.'
            );
            HTTP::redirectTrustedURL($registrationUrl, $params);
        } else {
            Logger::debug(
                self::DEBUG_PREFIX . 'Displaying registration notification. After that, redirecting to \''
                . $registrationUrl . '\'.'
            );
            $url = Module::getModuleURL(self::REDIRECT_NOTIFY);
            HTTP::redirectTrustedURL(
                $url,
                [
                    self::PARAM_STATE_ID => $stateId,
                    self::PARAM_REGISTRATION_URL => $registrationUrl,
                ]
            );
        }
    }

    protected function registerChooseVoAndGroup(array &$request, array $registrationData): void
    {
        $request[self::REDIRECT_PARAMS] = [
            self::PARAM_REGISTRATION_DATA => $registrationData,
            self::PARAM_REGISTRATION_URL => $this->registrarUrl,
        ];

        $stateId = State::saveState($request, self::STAGE);
        $url = Module::getModuleURL(self::REDIRECT_SELECT);
        HTTP::redirectTrustedURL($url, [
            self::PARAM_STATE_ID => $stateId,
        ]);
    }

    private function getRegistrationData($user, Facility $facility, string $spEntityId, array $facilityAttributes): array
    {
        if (null === $this->rpcAdapter) {
            throw new Exception(self::DEBUG_PREFIX . 'No RPC adapter available, cannot fetch registration data');
        }
        $voShortNames = $facilityAttributes[self::VO_SHORT_NAMES];
        if (empty($voShortNames)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Service misconfiguration - service \'' . $spEntityId . '\' has ' . 'membership check enabled, but does not have any resources for membership management created.'
            );
        }
        $voShorNamesForRegistration = $this->getRegistrationVoShortNames($user, $voShortNames);

        return $this->getRegistrationGroups($facility, $voShorNamesForRegistration);
    }

    private function getRegistrationVoShortNames(User $user, array $voShortNames): array
    {
        $activeMemberVos = [];
        $expiredMemberVosWithRegForm = [];
        $notMemberVosWithRegForm = [];
        foreach ($voShortNames as $voShortName) {
            try {
                $vo = $this->adapter->getVoByShortName($voShortName);
                if (empty($vo)) {
                    Logger::debug(
                        self::DEBUG_PREFIX . 'Could not fetch VO with shortName \'' . $voShortName . '\', skip it.'
                    );
                    continue;
                }
                $member = $this->rpcAdapter->getMemberByUser($user, $vo);
                if (Member::VALID === $member->getStatus()) {
                    // VALID HERE, CAN REGISTER INTO GROUPS
                    $activeMemberVos[] = $voShortName;
                    Logger::debug(
                        self::DEBUG_PREFIX . 'User is valid in VO with short name \'' . $voShortName
                        . '\', groups of this VO will be included in registration list.'
                    );
                } elseif (Member::EXPIRED === $member->getStatus()) {
                    // EXPIRED HERE, LETS CHECK IF IT HAS REG. FORM SO MEMBERSHIP CAN BE EXTENDED
                    Logger::debug(
                        self::DEBUG_PREFIX . 'User is expired in the VO with short name \'' . $voShortName
                        . '\', checking registration form availability.'
                    );
                    if ($this->rpcAdapter->hasRegistrationFormByVoShortName($voShortName)) {
                        $expiredMemberVosWithRegForm[] = $voShortName;
                        Logger::debug(
                            self::DEBUG_PREFIX . 'User is expired in the VO with short name \'' . $voShortName
                            . '\', groups of this VO will be included in registration list as it has got extension form.'
                        );
                    }
                } else {
                    Logger::debug(
                        self::DEBUG_PREFIX . 'User is a member in the VO with shortName \'' . $voShortName
                        . '\' but not valid nor expire. VO will be ignored for registration.'
                    );
                }
            } catch (\Exception $exception) {
                Logger::debug(
                    self::DEBUG_PREFIX . 'User is not a member in the VO with shortName \'' . $voShortName . '\'.'
                );
                if ($this->rpcAdapter->hasRegistrationFormByVoShortName($voShortName)) {
                    $notMemberVosWithRegForm[] = $voShortName;
                    Logger::debug(
                        self::DEBUG_PREFIX . 'User is not a member in the VO with short name \'' . $voShortName
                        . '\', groups of this VO will be included in registration list as it has got registration form.'
                    );
                } else {
                    Logger::debug(
                        self::DEBUG_PREFIX . 'VO with shortName \'' . $voShortName
                        . '\' does not have registration form, ignore it.'
                    );
                }
            }
        }

        return array_merge($activeMemberVos, $expiredMemberVosWithRegForm, $notMemberVosWithRegForm);
    }

    private function getRegistrationGroups(Facility $facility, array $voShortNames): array
    {
        $spGroups = $this->adapter->getSpGroupsByFacility($facility);
        $registrationData = [];
        foreach ($spGroups as $group) {
            $nameParts = explode(':', $group->getUniqueName(), 2);
            $voName = $nameParts[0];
            $groupName = $nameParts[1];

            if (!in_array($voName, $voShortNames, true)) {
                continue;
            }

            if (PerunConstants::GROUP_MEMBERS === $groupName) {
                // this is covered by the VO, which has got the reg. form instead of this group
                Logger::debug(
                    self::DEBUG_PREFIX . 'Group \'' . $group->getUniqueName() . '\' added to the registration list.'
                );
                $registrationData[] = $group;
            } else {
                if ($this->rpcAdapter->hasRegistrationForm($group->getId(), PerunConstants::GROUP)) {
                    $registrationData[] = $group;
                    Logger::debug(
                        self::DEBUG_PREFIX . 'Group \'' . $group->getUniqueName() . '\' added to the registration list.'
                    );
                }
            }
        }

        return $registrationData;
    }
}
