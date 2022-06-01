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
use SimpleSAML\Module\perun\PerunConstants;
use SimpleSAML\Utils\HTTP;

/**
 * Class checks if the user has approved given aup, and forwards to approval page if not.
 */
class PerunAup extends ProcessingFilter
{
    public const STAGE = 'perun:PerunAup';

    public const DEBUG_PREFIX = self::STAGE . ' - ';

    public const CALLBACK = 'perun/perun_aup_callback.php';

    public const REDIRECT = 'perun/perun_aup.php';

    public const TEMPLATE = 'perun:perun-aup-tpl.php';

    public const PARAM_STATE_ID = PerunConstants::STATE_ID;

    public const PARAM_APPROVAL_URL = 'approvalUrl';

    public const INTERFACE = 'interface';

    public const AUP_ATTR = 'attribute';

    public const AUP_VALUE = 'value';

    public const APPROVAL_URL = 'approval_url';

    public const CALLBACK_PARAMETER_NAME = 'callback_parameter_name';

    public const PERUN_APPROVAL_URL = 'perun_approval_url';

    private $adapter;

    private $aupAttr;

    private $aupValue;

    private $approvalUrl;

    private $callbackParameterName;

    private $perunApprovalUrl;

    private $config;

    private $filterConfig;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $this->config = $config;
        $this->filterConfig = Configuration::loadFromArray($config);

        $interface = $this->filterConfig->getString(self::INTERFACE, Adapter::RPC);
        $this->adapter = Adapter::getInstance($interface);

        $this->aupAttr = $this->filterConfig->getString(self::AUP_ATTR, null);
        if (empty($this->aupAttr)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Invalid configuration: no attribute containing approved AUP ' . 'has been configured. Use option \'' . self::AUP_ATTR . '\' to configure the name of the Perun' . 'attribute, which should contain the approved AUP version.'
            );
        }

        $this->aupValue = $this->filterConfig->getString(self::AUP_VALUE, null);
        if (empty($this->aupValue)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Invalid configuration: no value signaling AUP which needs to be approved ' . 'has been configured. Use option \'' . self::AUP_VALUE . '\' to configure the value, which needs to ' . 'be present in the attribute containing the approved AUP version.'
            );
        }

        $this->approvalUrl = $this->filterConfig->getString(self::APPROVAL_URL, null);
        $this->callbackParameterName = $this->filterConfig->getString(self::CALLBACK_PARAMETER_NAME, null);
        $this->perunApprovalUrl = $this->filterConfig->getString(self::PERUN_APPROVAL_URL, null);
        if (empty($this->approvalUrl) && empty($this->callbackParameterName) && empty($this->perunApprovalUrl)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Invalid configuration: no URL where user should approve the AUP ' . 'has been configured. Use option \'' . self::APPROVAL_URL . '\' to configure the URL and ' . 'option \'' . self::CALLBACK_PARAMETER_NAME . '\' to configure name of the callback parameter. 
                . If you wish to use the Perun registrar, use the option \'' . self::PERUN_APPROVAL_URL . '\'.'
            );
        }
    }

    public function process(&$request)
    {
        assert(is_array($request));
        assert(!empty($request[PerunConstants::PERUN][PerunConstants::USER]));

        if (empty($request[PerunConstants::PERUN][PerunConstants::USER])) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Request does not contain Perun user. Did you configure ' . PerunUser::STAGE . ' filter before this filter in the processing chain?'
            );
        }
        $user = $request[PerunConstants::PERUN][PerunConstants::USER];

        $aupAttr = null;
        $userAttributesValues = $this->adapter->getUserAttributesValues($user, [$this->aupAttr]);
        if (empty($userAttributesValues) || empty($userAttributesValues[$this->aupAttr])) {
            Logger::warning(
                self::DEBUG_PREFIX . 'Attribute \'' . $this->aupAttr . '\' is empty. Probably could not be '
                . 'fetched. Redirecting user to approve AUP.'
            );
        } else {
            $aupAttr = $userAttributesValues[$this->aupAttr];
        }

        if ($aupAttr === $this->aupValue) {
            Logger::info(
                self::DEBUG_PREFIX . 'User approved AUP did match the expected value, continue processing.'
            );

            return;
        }
        Logger::info(
            self::DEBUG_PREFIX . 'User did not approve the expected AUP. Expected value \''
            . $this->aupValue . '\', actual value \'' . $aupAttr . '\'. Redirecting user to AUP approval page.'
        );
        $this->redirect($request);
    }

    private function redirect(&$request): void
    {
        $request[PerunConstants::CONTINUE_FILTER_CONFIG] = $this->config;
        $stateId = State::saveState($request, self::STAGE);
        $callback = Module::getModuleURL(self::CALLBACK, [
            self::PARAM_STATE_ID => $stateId,
        ]);
        if (!empty($this->approvalUrl) && !empty($this->callbackParameterName)) {
            Logger::debug(
                self::DEBUG_PREFIX . 'Redirecting to \'' . $this->approvalUrl . ', callback parameter \''
                . $this->callbackParameterName . '\' with value \'' . $callback . '\''
            );
            HTTP::redirectTrustedURL($this->approvalUrl, [
                $this->callbackParameterName => $callback,
            ]);
        } elseif (!empty($this->perunApprovalUrl)) {
            $params[PerunConstants::TARGET_NEW] = $callback;
            $params[PerunConstants::TARGET_EXISTING] = $callback;
            $params[PerunConstants::TARGET_EXTENDED] = $callback;

            $url = Module::getModuleURL(self::REDIRECT);
            $approvalUrl = HTTP::addURLParameters($this->perunApprovalUrl, $params);
            Logger::debug(
                self::DEBUG_PREFIX . 'Redirecting to \'' . self::REDIRECT . ', approval URL \''
                . $approvalUrl . '\''
            );
            HTTP::redirectTrustedURL($url, [
                self::PARAM_APPROVAL_URL => $approvalUrl,
            ]);
        } else {
            throw new Exception(self::DEBUG_PREFIX . 'No configuration for AUP approval enabled. Cannot proceed');
        }
    }
}
