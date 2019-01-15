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

	const UID_ATTR = 'uidAttr';
	const INTERFACE_PROPNAME = 'interface';
	const PERUN_AUPS_ATTR = 'perunAupsAttr';
	const PERUN_USER_AUP_ATTR = 'perunUserAupAttr';
	const PERUN_VO_AUP_ATTR = 'perunVoAupAttr';
	const PERUN_FACILITY_REQ_AUPS_ATTR = 'perunFacilityReqAupsAttr';
	const PERUN_FACILITY_VO_SHORT_NAMES = 'facilityVoShortNames';



	private $uidAttr;
	private $perunAupsAttr;
	private $perunUserAupAttr;
	private $perunVoAupAttr;
	private $perunFacilityReqAupsAttr;
	private $perunFacilityVoShortNames;
	private $interface;

	/**
	 * @var sspmod_perun_Adapter
	 */
	private $adapter;

	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		if (!isset($config[self::UID_ATTR])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option '" . self::UID_ATTR . "'.");
		}
		if (!isset($config[self::PERUN_AUPS_ATTR])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option '" . self::PERUN_AUPS_ATTR . "'.");
		}
		if (!isset($config[self::PERUN_USER_AUP_ATTR])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option '" . self::PERUN_USER_AUP_ATTR . "'.");
		}
		if (!isset($config[self::PERUN_VO_AUP_ATTR])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option '" . self::PERUN_VO_AUP_ATTR . "'.");
		}
		if (!isset($config[self::PERUN_FACILITY_REQ_AUPS_ATTR])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option '" . self::PERUN_FACILITY_REQ_AUPS_ATTR . "'.");
		}
		if (!isset($config[self::PERUN_FACILITY_VO_SHORT_NAMES])) {
			throw new SimpleSAML_Error_Exception("perun:ForceAup: missing mandatory configuration option '" . self::PERUN_FACILITY_REQ_AUPS_ATTR . "'.");
		}
		if (!isset($config[self::INTERFACE_PROPNAME])) {
			$config[self::INTERFACE_PROPNAME] = sspmod_perun_Adapter::RPC;
		}


		$this->uidAttr       = (string) $config[self::UID_ATTR];
		$this->perunAupsAttr = (string) $config[self::PERUN_AUPS_ATTR];
		$this->perunUserAupAttr   = (string) $config[self::PERUN_USER_AUP_ATTR];
		$this->perunVoAupAttr = (string) $config[self::PERUN_VO_AUP_ATTR];
		$this->perunFacilityReqAupsAttr= (string) $config[self::PERUN_FACILITY_REQ_AUPS_ATTR];
		$this->perunFacilityVoShortNames = (string) $config[self::PERUN_FACILITY_VO_SHORT_NAMES];
		$this->interface = (string) $config[self::INTERFACE_PROPNAME];
		$this->adapter = sspmod_perun_Adapter::getInstance($this->interface);

	}

	/**
	 * @param $request
	 */
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

		try {
			$facilities = $this->adapter->getFacilitiesByEntityId($request['SPMetadata']['entityid']);

			$requiredAups = array();
			$voShortNames = array();
			
			if ($this->is_iterable($facilities)) {
				foreach ($facilities as $facility) {
					$facilityAups = $this->adapter->getFacilityAttribute($facility, $this->perunFacilityReqAupsAttr);

					if (!is_null($facilityAups)) {
						foreach ($facilityAups as $facilityAup) {
							array_push($requiredAups, $facilityAup);
						}
					}

					$facilityVoShortNames = $this->adapter->getFacilityAttribute($facility, $this->perunFacilityVoShortNames);

					if (!is_null($facilityVoShortNames)) {
						foreach ($facilityVoShortNames as $facilityVoShortName) {
							array_push($voShortNames, $facilityVoShortName);
						}
					}
				}
            }

			if (empty($requiredAups) && empty($voShortNames)) {
				SimpleSAML\Logger::debug('Perun.ForceAup - No required Aups for facility with EntityId: ' . $request['SPMetadata']['entityid'] );
				return;
			}

			$perunAups = $this->adapter->getEntitylessAttribute($this->perunAupsAttr);

			$userAups = $this->adapter->getUserAttributes($user, array($this->perunUserAupAttr))[$this->perunUserAupAttr];

			if (is_null($userAups)) {
				$userAups = array();
			}

			$voAups = $this->getVoAups($voShortNames);

			$newAups = array();

			if (!empty($perunAups)) {
				foreach ($requiredAups as $requiredAup) {

					$aups = json_decode($perunAups[$requiredAup]);
					$latest_aup = $this->getLatestAup($aups);

					if (array_key_exists($requiredAup, $userAups)) {
						$userAupsList = json_decode($userAups[$requiredAup]);
						$userLatestAup = $this->getLatestAup($userAupsList);

						if ($latest_aup->date === $userLatestAup->date) {
							break;
						}

					}
					$newAups[$requiredAup] = $latest_aup;
				}
			}

			if (!empty($voAups)) {
				foreach ($voAups as $voShortName => $voAup) {
					$voAupsList = json_decode($voAup);
					$latest_aup = $this->getLatestAup($voAupsList);

					if (array_key_exists($voShortName, $userAups)) {
						$userAupsList = json_decode($userAups[$voShortName]);
						$userLatestAup = $this->getLatestAup($userAupsList);

						if ($latest_aup->date === $userLatestAup->date) {
							break;
						}

					}

					$newAups[$voShortName] = $latest_aup;
				}
			}

		} catch (Exception $ex) {
			SimpleSAML\Logger::warning("perun:ForceAup - " . $ex->getMessage());
			$newAups = array();
		}

		SimpleSAML\Logger::debug("perun:ForceAup - NewAups: " . print_r($newAups, true));

		if (!empty($newAups)) {
			$request[self::UID_ATTR] = $this->uidAttr;
			$request[self::PERUN_USER_AUP_ATTR] = $this->perunUserAupAttr;
			$request['newAups'] = $newAups;
			$id = SimpleSAML_Auth_State::saveState($request, 'perun:forceAup');
			$url = SimpleSAML\Module::getModuleURL('perun/force_aup_page.php');
			\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
		}

	}

	/**
	 * @param array $aups
	 * @return aup with the latest date
	 */
	public function getLatestAup(&$aups) {
		$latest_aup = $aups[0];
		foreach ($aups as $aup) {
			if ( new DateTime($latest_aup->date) < new DateTime($aup->date) ) {
				$latest_aup = $aup;
			}
		}
		return $latest_aup;
	}

	/**
	 * @param string[] $voShortNames
	 * @return array
	 */
	public function getVoAups(&$voShortNames) {
		$vos = array();
		foreach ($voShortNames as $voShortName) {
			$vo = $this->adapter->getVoByShortName($voShortName);
			if ($vo != null) {
				array_push($vos, $vo);
			}
		}

		$voAups = array();
		foreach ($vos as $vo) {
			$aups = $this->adapter->getVoAttributes($vo, array($this->perunVoAupAttr))[$this->perunVoAupAttr];
			if ($aups != null) {
				$voAups[$vo->getShortName()] = $aups;
			}
		}

		return $voAups;
	}

    /**
     * @param $var
     * @return bool
     */
    public function is_iterable($var) {
        return $var !== null
            && (is_array($var)
                || is_object($var)
                || $var instanceof Traversable
                || $var instanceof Iterator
                || $var instanceof IteratorAggregate
            );
    }


}


