<?php

/**
 * Class sspmod_perun_Auth_Process_ForceAup
 *
 * This filter check if user has attribute 'perunForceAttr' in perun set if so, it forces user to accept
 * usage policy specify in 'aupUrl' and unset 'perunForceAttr' and move the value to 'perunAupAttr'.
 * So attribute defined in 'perunAupAttr' is array which stores values defined in 'perunForceAttr' which means user
 * accept these versions of AUP.
 *
 * If you want to force user to accept usage policy, set 'perunForceAttr' to string specifying version of new policy
 * and let user authenticate.
 *
 * It uses Perun RPC. Configure it properly in config/module_perun.php.
 *
 * It relies on PerunIdentity filter. Configure it before this filter properly.
 */
class sspmod_perun_Auth_Process_ForceAup extends SimpleSAML_Auth_ProcessingFilter
{

	private $uidAttr;
	private $perunForceAttr;
	private $perunAupAttr;
	private $aupUrl;

	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		if (!isset($config['uidAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option 'uidAttr'.");
		}
		if (!isset($config['perunForceAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option 'perunForceAttr'.");
		}
		if (!isset($config['perunAupAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option 'perunAupAttr'.");
		}
		if (!isset($config['aupUrl'])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option 'aupUrl'.");
		}
		$this->uidAttr       = (string) $config['uidAttr'];
		$this->perunForceAttr = (string) $config['perunForceAttr'];
		$this->perunAupAttr   = (string) $config['perunAupAttr'];
		$this->aupUrl         = (string) $config['aupUrl'];
	}
	public function process(&$request)
	{
		assert('is_array($request)');

		if (isset($request['perun']['user'])) {
			/** allow IDE hint whisperer
			 * @var sspmod_perun_model_User $user
			 */
			$user = $request['perun']['user'];
		} else {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: " .
				"missing mandatory field 'perun.user' in request." .
				"Hint: Did you configured PerunIdentity filter before this filter?"
			);
		}

		// has to work with RPC (not LDAP) because it needs to update values of attributes.
		$rpc = sspmod_perun_Adapter::getInstance(sspmod_perun_Adapter::RPC);

		$forceAup = $rpc->getUserAttributes($user, array($this->perunForceAttr))[$this->perunForceAttr];

		if (!empty($forceAup)) {
			$request['uidAttr']  = $this->uidAttr;
			$request['perunForceAttr'] = $this->perunForceAttr;
			$request['perunAupAttr'] = $this->perunAupAttr;
			$request['aupUrl'] = $this->aupUrl;
			$request['aupVersion']  = $forceAup;
			$id  = SimpleSAML_Auth_State::saveState($request, 'perun:forceAup');
			$url = SimpleSAML_Module::getModuleURL('perun/force_aup_page.php');
			\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
		}

	}


}


