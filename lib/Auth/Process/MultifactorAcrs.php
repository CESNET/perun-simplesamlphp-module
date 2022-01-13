<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Disco;

/**
 * Auth proc filter, which should be used if Proxy provides MFA, and wants to first pass the request to the upstream
 * IdP. The Perun Disco page, tries to add new ACRs to the request, if MFA has been requested, to prevent the IdP from
 * failing. As a result, we then get modified requested ACRs, which should be restored to the previous (original) state
 * using this authproc filter. It should be run on one of the first places of the IdP authproc chain.
 */
class MultifactorAcrs extends ProcessingFilter
{
    public const CONFIG_FILE_NAME = 'module_perun.php';

    private const DEBUG_PREFIX = 'perun:RestoreAcrs';

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
    }

    public function process(&$request)
    {
        $this->restoreAcrs($request);
    }

    public static function addAndStoreAcrs(array &$state, array $acrsToAdd)
    {
        if (!empty($acrsToAdd)
            && !empty($state[Disco::SAML_REQUESTED_AUTHN_CONTEXT][Disco::STATE_AUTHN_CONTEXT_CLASS_REF])
            && sizeof($state[Disco::SAML_REQUESTED_AUTHN_CONTEXT][Disco::STATE_AUTHN_CONTEXT_CLASS_REF]) <= 1
            && in_array(
                Disco::MFA_PROFILE,
                $state[Disco::SAML_REQUESTED_AUTHN_CONTEXT][Disco::STATE_AUTHN_CONTEXT_CLASS_REF],
                true
            )
        ) {
            Logger::debug(
                self::DEBUG_PREFIX . ': Modifying ACRs list to pass regular authentication to the IdP to fallback to proxy MFA'
            );
            $original = [];
            $new = [];
            foreach ($state[Disco::SAML_REQUESTED_AUTHN_CONTEXT][Disco::STATE_AUTHN_CONTEXT_CLASS_REF] as $acr) {
                $original[] = $acr;
                $new[] = $acr;
            }
            foreach ($acrsToAdd as $acr) {
                $new[] = $acr;
            }
            $new = array_unique($new);

            unset($state[Disco::SAML_REQUESTED_AUTHN_CONTEXT][Disco::STATE_AUTHN_CONTEXT_CLASS_REF]);
            if (isset($state[Disco::SAML_REQUESTED_AUTHN_CONTEXT_ORIGINAL])) {
                unset($state[Disco::SAML_REQUESTED_AUTHN_CONTEXT_ORIGINAL]);
            }
            Logger::debug(self::DEBUG_PREFIX . ': original ACRs: ' . join(',', $original));
            $state[Disco::SAML_REQUESTED_AUTHN_CONTEXT_ORIGINAL] = $original;
            Logger::debug(self::DEBUG_PREFIX . ': ACRs after modification: ' . join(',', $new));
            $state[Disco::SAML_REQUESTED_AUTHN_CONTEXT][Disco::STATE_AUTHN_CONTEXT_CLASS_REF] = $new;
        }
    }

    private function restoreAcrs(&$request)
    {
        if (!empty($request[Disco::SAML_REQUESTED_AUTHN_CONTEXT_ORIGINAL])) {
            unset($request[Disco::SAML_REQUESTED_AUTHN_CONTEXT][Disco::STATE_AUTHN_CONTEXT_CLASS_REF]);
            $request[Disco::SAML_REQUESTED_AUTHN_CONTEXT][Disco::STATE_AUTHN_CONTEXT_CLASS_REF] =
                $request[Disco::SAML_REQUESTED_AUTHN_CONTEXT_ORIGINAL];
            unset($request[Disco::SAML_REQUESTED_AUTHN_CONTEXT_ORIGINAL]);
            Logger::debug(
                self::DEBUG_PREFIX . ': ACRS restored: '
                . join(',', $request[Disco::SAML_REQUESTED_AUTHN_CONTEXT][Disco::STATE_AUTHN_CONTEXT_CLASS_REF])
            );
        }
    }
}
