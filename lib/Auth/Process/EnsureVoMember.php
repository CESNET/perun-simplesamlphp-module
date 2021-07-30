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
use SimpleSAML\Utils\HTTP;

class EnsureVoMember extends ProcessingFilter
{
    public const ENSURE_VO_MEMBER = 'ensureVoMember';

    public const TRIGGER_ATTR = 'triggerAttr';

    public const VO_DEFS_ATTR = 'voDefsAttr';

    public const LOGIN_URL = 'loginURL';

    public const REGISTRAR_URL = 'registrarURL';

    public const INTERFACE_PROPNAME = 'interface';

    public const RPC = 'rpc';

    private $triggerAttr;

    private $voDefsAttr;

    private $adapter;

    private $loginUrlAttr;

    private $registrarUrl;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);

        if ($config === null) {
            throw new Exception(
                'perun:EnsureVoMember: Property  \'' . self::ENSURE_VO_MEMBER . '\' is missing or invalid!'
            );
        }

        $this->triggerAttr = $config->getString(self::TRIGGER_ATTR, '');

        if (empty($this->triggerAttr)) {
            throw new Exception(
                'perun:EnsureVoMember: Missing configuration option \'' . self::TRIGGER_ATTR . '\''
            );
        }

        $this->voDefsAttr = $config->getString(self::VO_DEFS_ATTR, '');

        if (empty($this->voDefsAttr)) {
            throw new Exception(
                'perun:EnsureVoMember: Missing configuration option \'' . self::VO_DEFS_ATTR . '\''
            );
        }

        $this->loginUrlAttr = $config->getString(self::LOGIN_URL, null);
        $this->registrarUrl = $config->getString(self::REGISTRAR_URL, null);

        $interface = $config->getString(self::INTERFACE_PROPNAME, self::RPC);
        $this->adapter = Adapter::getInstance($interface);
    }

    public function process(&$request)
    {
        if (isset($request['SPMetadata']['entityid'])) {
            $spEntityId = $request['SPMetadata']['entityid'];
        } else {
            throw new Exception(
                'perun:EnsureVoMember: Cannot find entityID of remote SP. ' .
                'hint: Do you have this filter in IdP context?'
            );
        }

        if (isset($request['perun']['user'])) {
            $user = $request['perun']['user'];
        } else {
            throw new Exception(
                'perun:EnsureVoMember: ' .
                'missing mandatory field \'perun.user\' in request.' .
                'Hint: Did you configured PerunIdentity filter before this filter?'
            );
        }

        $facility = $this->adapter->getFacilityByEntityId($spEntityId);

        if ($facility === null) {
            Logger::debug('perun:EnsureVoMember: skip execution - no facility provided');
            return;
        }

        $attrValues = $this->adapter->getFacilityAttributesValues(
            $facility,
            [$this->voDefsAttr, $this->triggerAttr, $this->loginUrlAttr]
        );

        $triggerAttrValue = $attrValues[$this->triggerAttr];
        if ($triggerAttrValue === null || $triggerAttrValue === false) {
            Logger::debug(
                'perun:EnsureVoMember: skip execution - attribute ' . self::TRIGGER_ATTR . ' is null or false'
            );
            return;
        }

        $voShortName = $attrValues[$this->voDefsAttr];
        if (empty($voShortName)) {
            Logger::debug(
                'perun:EnsureVoMember: skip execution - attribute ' . self::VO_DEFS_ATTR . ' has null or no value'
            );
            return;
        }

        $canAccess = $this->adapter->isUserInVo($user, $voShortName);
        if ($canAccess) {
            Logger::debug('perun:EnsureVoMember: user allowed to continue');
        } else {
            $this->redirect($request, $attrValues[$this->loginUrlAttr], $voShortName);
        }
    }

    private function redirect($request, $loginUrl, $voShortName)
    {
        if (! empty($voShortName) &&
            ! empty($this->registrarUrl) &&
            $this->adapter->hasRegistrationFormByVoShortName($voShortName)
        ) {
            $this->redirectToRegistration($loginUrl, $voShortName);
        } else {
            $this->redirectUnapproved($request);
        }
    }

    private function redirectToRegistration($loginUrl, $voShortName)
    {
        HTTP::redirectTrustedURL(
            $this->registrarUrl,
            [
                'vo' => $voShortName,
                'targetnew' => $loginUrl,
                'targetexisting' => $loginUrl,
            ]
        );
    }

    private function redirectUnapproved($request)
    {
        $id = State::saveState($request, 'perunauthorize:Perunauthorize');
        $url = Module::getModuleURL('perunauthorize/perunauthorize_403.php');

        $params = [];
        $params['StateId'] = $id;
        $params['administrationContact'] = $request['SPMetadata']['administrationContact'];
        $params['serviceName'] = $request['SPMetadata']['name']['en'];

        if (isset($request['SPMetadata']['InformationURL']['en'])) {
            $params['informationURL'] = $request['SPMetadata']['InformationURL']['en'];
        }

        HTTP::redirectTrustedURL($url, $params);
    }
}
