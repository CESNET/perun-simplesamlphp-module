<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Error\Exception;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\model;
use SimpleSAML\Logger;
use SimpleSAML\Auth\State;
use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Configuration;

/**
 * Class ForceAup
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
class ForceAup extends ProcessingFilter
{

    const UID_ATTR = 'uidAttr';
    const INTERFACE_PROPNAME = 'interface';
    const PERUN_AUPS_ATTR = 'perunAupsAttr';
    const PERUN_USER_AUP_ATTR = 'perunUserAupAttr';
    const PERUN_VO_AUP_ATTR = 'perunVoAupAttr';
    const PERUN_FACILITY_REQ_AUPS_ATTR = 'perunFacilityReqAupsAttr';
    const PERUN_FACILITY_VO_SHORT_NAMES_ATTR = 'perunFacilityVoShortNamesAttr';

    private $uidAttr;
    private $perunAupsAttr;
    private $perunUserAupAttr;
    private $perunVoAupAttr;
    private $perunFacilityReqAupsAttr;
    private $perunFacilityVoShortNames;

    /**
     * @var Adapter
     */
    private $adapter;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (!isset($config[self::UID_ATTR])) {
            throw new Exception(
                'perun:ForceAup: missing mandatory configuration option \'' . self::UID_ATTR . '\'.'
            );
        }
        if (!isset($config[self::PERUN_AUPS_ATTR])) {
            throw new Exception(
                'perun:ForceAup: missing mandatory configuration option \'' . self::PERUN_AUPS_ATTR . '\'.'
            );
        }
        if (!isset($config[self::PERUN_USER_AUP_ATTR])) {
            throw new Exception(
                'perun:ForceAup: missing mandatory configuration option \'' . self::PERUN_USER_AUP_ATTR . '\'.'
            );
        }
        if (!isset($config[self::PERUN_VO_AUP_ATTR])) {
            throw new Exception(
                'perun:ForceAup: missing mandatory configuration option \'' . self::PERUN_VO_AUP_ATTR . '\'.'
            );
        }
        if (!isset($config[self::INTERFACE_PROPNAME])) {
            $config[self::INTERFACE_PROPNAME] = Adapter::RPC;
        }

        $this->uidAttr = (string)$config[self::UID_ATTR];
        $this->perunAupsAttr = (string)$config[self::PERUN_AUPS_ATTR];
        $this->perunUserAupAttr = (string)$config[self::PERUN_USER_AUP_ATTR];
        $this->perunVoAupAttr = (string)$config[self::PERUN_VO_AUP_ATTR];
        $interface = (string)$config[self::INTERFACE_PROPNAME];
        $this->adapter = Adapter::getInstance($interface);

        $this->perunFacilityReqAupsAttr = (string)$config[self::PERUN_FACILITY_REQ_AUPS_ATTR];
        $this->perunFacilityVoShortNames = (string)$config[self::PERUN_FACILITY_VO_SHORT_NAMES_ATTR];
    }

    /**
     * @param $request
     */
    public function process(&$request)
    {
        assert(is_array($request));

        if (isset($request['perun']['user'])) {
            /** allow IDE hint whisperer
             * @var model\User $user
             */
            $user = $request['perun']['user'];
        } else {
            throw new Exception(
                'perun:ForceAup: ' .
                'missing mandatory field \'perun.user\' in request.' .
                'Hint: Did you configured PerunIdentity filter before this filter?'
            );
        }

        try {
            $facility = $this->adapter->getFacilityByEntityId($request['SPMetadata']['entityid']);

            if ($facility === null) {
                return;
            }

            $requiredAups = [];
            $voShortNames = [];

            $facilityAttrValues = $this->adapter->getFacilityAttributesValues(
                $facility,
                [$this->perunFacilityReqAupsAttr, $this->perunFacilityVoShortNames]
            );

            if (isset($this->perunFacilityReqAupsAttr, $facilityAttrValues) &&
                is_array($facilityAttrValues[$this->perunFacilityReqAupsAttr])) {
                foreach (array_values($facilityAttrValues[$this->perunFacilityReqAupsAttr]) as $facilityAup) {
                    array_push($requiredAups, $facilityAup);
                }
            }

            if (isset($this->perunFacilityVoShortNames, $facilityAttrValues) &&
                is_array($facilityAttrValues[$this->perunFacilityVoShortNames])) {
                foreach (array_values($facilityAttrValues[$this->perunFacilityVoShortNames]) as $facilityVoShortName) {
                    array_push($voShortNames, $facilityVoShortName);
                }
            }

            if (empty($requiredAups) && empty($voShortNames)) {
                Logger::debug(
                    'Perun.ForceAup - No required Aups for facility with EntityId: ' .
                    $request['SPMetadata']['entityid']
                );
                return;
            }

            $perunAupsAttr = $this->adapter->getEntitylessAttribute($this->perunAupsAttr);

            $perunAups = [];
            foreach ($perunAupsAttr as $key => $attr) {
                $perunAups[$key] = $attr['value'];
            }

            $userAups = $this->adapter->getUserAttributesValues(
                $user,
                [$this->perunUserAupAttr]
            )[$this->perunUserAupAttr];

            if ($userAups === null) {
                $userAups = [];
            }

            $voAups = $this->getVoAups($voShortNames);

            $newAups = [];

            if (!empty($perunAups)) {
                foreach ($requiredAups as $requiredAup) {
                    $aups = json_decode($perunAups[$requiredAup]);
                    $latest_aup = $this->getLatestAup($aups);

                    if (array_key_exists($requiredAup, $userAups)) {
                        $userAupsList = json_decode($userAups[$requiredAup]);
                        $userLatestAup = $this->getLatestAup($userAupsList);

                        if ($latest_aup->date === $userLatestAup->date) {
                            continue;
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
                            continue;
                        }
                    }

                    $newAups[$voShortName] = $latest_aup;
                }
            }
        } catch (\Exception $ex) {
            Logger::warning('perun:ForceAup - ' . $ex->getMessage());
            $newAups = [];
        }

        Logger::debug('perun:ForceAup - NewAups: ' . print_r($newAups, true));

        if (!empty($newAups)) {
            $request[self::UID_ATTR] = $this->uidAttr;
            $request[self::PERUN_USER_AUP_ATTR] = $this->perunUserAupAttr;
            $request['newAups'] = $newAups;
            $id = State::saveState($request, 'perun:forceAup');
            $url = Module::getModuleURL('perun/force_aup_page.php');
            HTTP::redirectTrustedURL($url, ['StateId' => $id]);
        }
    }

    /**
     * @param array $aups
     * @return aup with the latest date
     */
    public function getLatestAup(&$aups)
    {
        $latest_aup = $aups[0];
        foreach ($aups as $aup) {
            if (new \DateTime($latest_aup->date) < new \DateTime($aup->date)) {
                $latest_aup = $aup;
            }
        }
        return $latest_aup;
    }

    /**
     * @param string[] $voShortNames
     * @return array
     */
    public function getVoAups(&$voShortNames)
    {
        $vos = [];
        foreach ($voShortNames as $voShortName) {
            $vo = $this->adapter->getVoByShortName($voShortName);
            if ($vo !== null) {
                array_push($vos, $vo);
            }
        }

        $voAups = [];
        foreach ($vos as $vo) {
            $aups = $this->adapter->getVoAttributesValues($vo, [$this->perunVoAupAttr])[$this->perunVoAupAttr];
            if ($aups !== null) {
                $voAups[$vo->getShortName()] = $aups;
            }
        }

        return $voAups;
    }
}
