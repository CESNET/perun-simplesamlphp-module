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
use SimpleSAML\Module\perun\model\User;
use SimpleSAML\Module\perun\PerunConstants;
use SimpleSAML\Utils\HTTP;

/**
 * Class tries to find user in Perun using the extLogin and extSourceName (in case of RPC adapter).
 *
 * If the user cannot be found, it redirects user to the registration URL.
 */
class PerunUser extends ProcessingFilter
{
    public const STAGE = 'perun:PerunUser';
    public const DEBUG_PREFIX = self::STAGE . ' - ';

    public const CALLBACK = 'perun/perun_user_callback.php';
    public const REDIRECT = 'perun/perun_user.php';
    public const TEMPLATE = 'perun:perun-user-tpl.php';

    public const PARAM_REGISTRATION_URL = 'registrationUrl';
    public const PARAM_STATE_ID = PerunConstants::STATE_ID;

    public const INTERFACE = 'interface';
    public const UID_ATTRS = 'uid_attrs';
    public const IDP_ID_ATTR = 'idp_id_attr';
    public const REGISTER_URL = 'register_url';
    public const CALLBACK_PARAMETER_NAME = 'callback_parameter_name';
    public const PERUN_REGISTER_URL = 'perun_register_url';

    private $adapter;
    private $idpEntityIdAttr;
    private $userIdAttrs;
    private $registerUrl;
    private $callbackParameterName;
    private $perunRegisterUrl;
    private $config;
    private $filterConfig;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $this->config = $config;
        $this->filterConfig = Configuration::loadFromArray($config);

        $interface = $this->filterConfig->getString(self::INTERFACE, Adapter::RPC);

        $this->adapter = Adapter::getInstance($interface);
        $this->userIdAttrs = $this->filterConfig->getArray(self::UID_ATTRS, []);
        if (empty($this->userIdAttrs)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Invalid configuration: no attributes configured for extracting UID. Use option \'' . self::UID_ATTRS . '\' to configure list of attributes, that should be considered as IDs for a user'
            );
        }
        $this->idpEntityIdAttr = $this->filterConfig->getString(self::IDP_ID_ATTR, null);
        if (empty($this->idpEntityIdAttr)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Invalid configuration: no attribute containing IDP ID has been configured. Use option \'' . self::IDP_ID_ATTR . '\' to configure the name of the attribute, that has been previously used in the configuration of filter \'perun:ExtractIdpEntityId\''
            );
        }
        $this->registerUrl = $this->filterConfig->getString(self::REGISTER_URL, null);
        $this->callbackParameterName = $this->filterConfig->getString(self::CALLBACK_PARAMETER_NAME, null);
        $this->perunRegisterUrl = $this->filterConfig->getString(self::PERUN_REGISTER_URL, null);
        if (empty($this->registerUrl) && empty($this->callbackParameterName) && empty($this->perunRegisterUrl)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Invalid configuration: no URL where user should register for the account has been configured. Use option \'' . self::REGISTER_URL . '\' to configure the URL and option \'' . self::CALLBACK_PARAMETER_NAME . '\' to configure name of the callback parameter. 
                . If you wish to use the Perun registrar, use the option \'' . self::PERUN_REGISTER_URL . '\'.'
            );
        }
    }

    public function process(&$request)
    {
        assert(is_array($request));
        assert(array_key_exists(PerunConstants::ATTRIBUTES, $request));

        $uids = [];
        foreach ($this->userIdAttrs as $uidAttr) {
            if (isset($request[PerunConstants::ATTRIBUTES][$uidAttr][0])) {
                $uids[] = $request[PerunConstants::ATTRIBUTES][$uidAttr][0];
            }
        }
        if (empty($uids)) {
            $serializedUids = implode(', ', $this->userIdAttrs);
            throw new Exception(
                self::DEBUG_PREFIX . 'missing at least one of mandatory attributes [' . $serializedUids . '] in request.'
            );
        }

        if (!empty($request[PerunConstants::ATTRIBUTES][$this->idpEntityIdAttr][0])) {
            $idpEntityId = $request[PerunConstants::ATTRIBUTES][$this->idpEntityIdAttr][0];
        } else {
            throw new Exception(
                self::DEBUG_PREFIX . 'Cannot find entityID of source IDP. Did you properly configure ' . ExtractRequestAttribute::STAGE . ' filter before this filter in the processing chain?'
            );
        }

        $user = $this->adapter->getPerunUser($idpEntityId, $uids);

        if (!empty($user)) {
            $this->processUser($request, $user, $uids);
        } else {
            $this->register($request, $uids);
        }
    }

    private function processUser(array &$request, User $user, array $uids): void
    {
        if (!isset($request[PerunConstants::PERUN])) {
            $request[PerunConstants::PERUN] = [];
        }

        $request[PerunConstants::PERUN][PerunConstants::USER] = $user;

        $logUids = implode(', ', $uids);
        Logger::info(
            self::DEBUG_PREFIX . 'Perun user with identity/ies: \'' . $logUids . '\' has been found. Setting user ' . $user->getName() . ' with id: ' . $user->getId() . ' to the request.'
        );
    }

    private function register(array &$request, array $uids): void
    {
        $request[PerunConstants::CONTINUE_FILTER_CONFIG] = $this->config;
        $stateId = State::saveState($request, self::STAGE);
        $callback = Module::getModuleURL(self::CALLBACK, [
            self::PARAM_STATE_ID => $stateId,
        ]);
        Logger::debug(self::DEBUG_PREFIX . 'Produced callback URL \'' . $callback . '\'');
        $url = '';
        $params = [];

        if (!empty($this->registerUrl) && !empty($this->callbackParameterName)) {
            $url = $this->registerUrl;
            $params[$this->callbackParameterName] = $callback;
            Logger::debug(
                self::DEBUG_PREFIX . 'Redirecting to \'' . $this->registerUrl . ', callback parameter \'' . $this->callbackParameterName . '\' set to value \'' . $callback . '\'.'
            );
        } elseif (!empty($this->perunRegisterUrl)) {
            $perunParams[PerunConstants::TARGET_NEW] = $callback;
            $perunParams[PerunConstants::TARGET_EXISTING] = $callback;
            $perunParams[PerunConstants::TARGET_EXTENDED] = $callback;
            $registrationUrl = HTTP::addURLParameters($this->perunRegisterUrl, $perunParams);

            $url = Module::getModuleURL(self::REDIRECT);
            $params[self::PARAM_REGISTRATION_URL] = $registrationUrl;
            Logger::debug(
                self::DEBUG_PREFIX . 'Redirecting to \'' . self::REDIRECT . ', param registration URL \'' . $registrationUrl . '\'.'
            );
        } else {
            throw new Exception(self::DEBUG_PREFIX . 'No configuration for registration set. Cannot proceed.');
        }

        HTTP::redirectTrustedURL($url, $params);
        $logUids = implode(', ', $uids);
        Logger::info(
            self::DEBUG_PREFIX . 'Perun user with identity/ies: \'' . $logUids . '\' has not been found. User has been redirected to registration.'
        );
    }
}
