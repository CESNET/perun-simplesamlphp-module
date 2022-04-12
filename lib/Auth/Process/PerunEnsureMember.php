<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\model\Member;
use SimpleSAML\Module\perun\PerunConstants;
use SimpleSAML\Utils\HTTP;

class PerunEnsureMember extends ProcessingFilter
{
    const LOG_PREFIX = 'perun:PerunEnsureMember: ';

    const REGISTER_URL = 'registerUrl';
    const VO_SHORT_NAME = 'voShortName';
    const GROUP_NAME = 'groupName';
    const INTERFACE_PROPNAME = 'interface';
    const CALLBACK_PARAMETER_NAME = 'callbackParameterName';
    const RPC = 'rpc';

    const CALLBACK = 'perun/perun_ensure_member_callback.php';
    const REDIRECT = 'perun/perun_ensure_member.php';

    public const STAGE = 'perun:PerunEnsureMember';
    public const PARAM_STATE_ID = PerunConstants::STATE_ID;
    public const PARAM_REGISTRATION_URL = 'registrationUrl';
    public const TEMPLATE = 'perun:perun-ensure-member-tpl.php';

    private $config;
    private $filterConfig;
    private $registerUrl;
    private $voShortName;
    private $groupName;
    private $callbackParameterName;
    private $adapter;
    private $rpcAdapter;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $this->config = $config;
        $this->filterConfig = Configuration::loadFromArray($config);
        
        $this->registerUrl = $this->filterConfig->getString(self::REGISTER_URL, '');
        if (empty($this->registerUrl)) {
            throw new Exception(self::LOG_PREFIX . 'Missing configuration option \'' . self::REGISTER_URL . '\'');
        }

        $this->voShortName = $this->filterConfig->getString(self::VO_SHORT_NAME, '');
        if (empty($this->voShortName)) {
            throw new Exception(self::LOG_PREFIX . 'Missing configuration option \'' . self::VO_SHORT_NAME . '\'');
        }

        $this->callbackParameterName = $this->filterConfig->getString(self::CALLBACK_PARAMETER_NAME, '');
        if (empty($this->callbackParameterName)) {
            throw new Exception(self::LOG_PREFIX . 'Missing configuration option \'' . self::CALLBACK_PARAMETER_NAME . '\'');
        }

        $this->groupName = $this->filterConfig->getString(self::GROUP_NAME, '');

        $interface = $this->filterConfig->getString(self::INTERFACE_PROPNAME, self::RPC);
        $this->adapter = Adapter::getInstance($interface);

        $this->rpcAdapter = Adapter::getInstance(self::RPC);
    }

    public function process(&$request)
    {
        if (isset($request[PerunConstants::PERUN][PerunConstants::USER])) {
            $user = $request[PerunConstants::PERUN][PerunConstants::USER];
        } else {
            throw new Exception(
                self::LOG_PREFIX . 'Missing mandatory field \'perun.user\' in request.' . 'Hint: Did you configured PerunIdentity filter before this filter?'
            );
        }

        $vo = $this->adapter->getVoByShortName($this->voShortName);
        if (null === $vo) {
            throw new Exception(self::LOG_PREFIX . 'VO with voShortName \'' . self::VO_SHORT_NAME . '\' not found.');
        }

        $this->handleUser($user, $vo, $request);
    }

    private function handleUser($user, $vo, $request): void
    {
        // In this case, we can deal with empty groupName in the same way as with the user which is in the group
        $isUserInGroup = empty($this->groupName) || $this->isUserInGroup($this->groupName, $user, $vo);
        $memberStatus = $this->adapter->getMemberStatusByUserAndVo($user, $vo);

        if (Member::VALID === $memberStatus && $isUserInGroup) {
            Logger::debug(self::LOG_PREFIX . 'User is allowed to continue');
            return;
        }

        $memberStatus = $this->rpcAdapter->getMemberStatusByUserAndVo($user, $vo);
        $voHasRegistrationForm = $this->rpcAdapter->hasRegistrationForm($vo->getId(), PerunConstants::VO);
        $groupHasRegistrationForm = !empty($this->groupName) && $this->groupHasRegistrationForm($vo, $this->groupName);

        if (Member::VALID === $memberStatus && $isUserInGroup) {
            Logger::debug(self::LOG_PREFIX . 'User is allowed to continue');
        } elseif (Member::VALID === $memberStatus && !$isUserInGroup && $groupHasRegistrationForm) {
            Logger::debug(self::LOG_PREFIX . 'User is not valid in group ' . $this->groupName . ' - sending to registration');
            $this->register($request, $this->groupName);
        } elseif (null === $memberStatus && $voHasRegistrationForm && $isUserInGroup) {
            Logger::debug(self::LOG_PREFIX . 'User is not member of vo ' . $this->voShortName . ' - sending to registration');
            $this->register($request);
        } elseif (null === $memberStatus && $voHasRegistrationForm && !$isUserInGroup && $groupHasRegistrationForm) {
            Logger::debug(self::LOG_PREFIX . 'User is not member of vo ' . $this->voShortName . ' - sending to registration');
            $this->register($request, $this->groupName);
        } elseif (Member::EXPIRED === $memberStatus && $voHasRegistrationForm && $isUserInGroup) {
            Logger::debug(self::LOG_PREFIX . 'User is expired - sending to registration');
            $this->register($request);
        } elseif (Member::EXPIRED === $memberStatus && $voHasRegistrationForm && !$isUserInGroup && $groupHasRegistrationForm) {
            Logger::debug(self::LOG_PREFIX . 'User is expired and is not in group ' . $this->groupName . ' - sending to registration');
            $this->register($request, $this->groupName);
        } else {
            Logger::debug(self::LOG_PREFIX . 'User is not valid in vo/group and cannot be sent to the registration - sending to unauthorized');
            PerunIdentity::unauthorized($request);
        }
    }

    private function isUserInGroup($groupName, $user, $vo): bool
    {
        $memberGroups = $this->adapter->getGroupsWhereMemberIsActive($user, $vo);

        foreach ($memberGroups as $group) {
            if ($groupName === $group->getName()) {
                return true;
            }
        }

        return false;
    }

    private function groupHasRegistrationForm($vo, $groupName): bool
    {
        try {
            $group = $this->adapter->getGroupByName($vo, $groupName);
        } catch (Exception $e) {
            $group = null;
        }

        if (null !== $group) {
            return $this->rpcAdapter->hasRegistrationForm($group->getId(), PerunConstants::GROUP);
        }

        return false;
    }

    private function register(array &$request, $groupName = null): void
    {
        $request[PerunConstants::CONTINUE_FILTER_CONFIG] = $this->config;
        $stateId = State::saveState($request, self::STAGE);

        $callback = Module::getModuleURL(self::CALLBACK, [
            self::PARAM_STATE_ID => $stateId,
        ]);

        Logger::debug(self::LOG_PREFIX . 'Produced callback URL \'' . $callback . '\'');
        $params = [];

        if (!empty($this->callbackParameterName)) {
            $registrationUrl = $this->registerUrl . '?vo=' . $this->voShortName;
            if (null !== $groupName) {
                $registrationUrl .= '&group=' . $groupName;
            }

            $params[PerunConstants::TARGET_NEW] = $callback;
            $params[PerunConstants::TARGET_EXISTING] = $callback;
            $params[PerunConstants::TARGET_EXTENDED] = $callback;

            Logger::debug(
                self::LOG_PREFIX . 'Redirecting to \'' . $registrationUrl . ', callback parameter \'' . $this->callbackParameterName . '\' set to value \'' . $callback . '\'.'
            );

            HTTP::redirectTrustedURL($registrationUrl, $params);
        } else {
            throw new Exception(self::LOG_PREFIX . 'No configuration for registration set. Cannot proceed.');
        }
    }
}
