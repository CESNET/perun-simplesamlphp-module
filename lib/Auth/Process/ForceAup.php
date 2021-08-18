<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use DateTime;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\model;
use SimpleSAML\Utils\HTTP;

/**
 * Class ForceAup
 *
 * This filter check if user has attribute 'perunForceAttr' in perun set if so, it forces user to accept usage policy
 * specify in 'aupUrl' and unset 'perunForceAttr' and move the value to 'perunAupAttr'. So attribute defined in
 * 'perunAupAttr' is array which stores values defined in 'perunForceAttr' which means user accept these versions of
 * AUP.
 *
 * If you want to force user to accept usage policy, set 'perunForceAttr' to string specifying version of new policy and
 * let user authenticate.
 *
 * It uses Perun RPC. Configure it properly in config/module_perun.php.
 *
 * It relies on PerunIdentity filter. Configure it before this filter properly.
 */
class ForceAup extends ProcessingFilter
{
    public const UID_ATTR = 'uidAttr';

    public const INTERFACE_PROPNAME = 'interface';

    public const PERUN_AUPS_ATTR = 'perunAupsAttr';

    public const PERUN_USER_AUP_ATTR = 'perunUserAupAttr';

    public const PERUN_VO_AUP_ATTR = 'perunVoAupAttr';

    public const PERUN_FACILITY_REQ_AUPS_ATTR = 'perunFacilityReqAupsAttr';

    public const PERUN_FACILITY_VO_SHORT_NAMES_ATTR = 'perunFacilityVoShortNamesAttr';

    private const DATETIME_FORMAT = 'Y-m-d';

    private $uidAttr;

    private $perunAupsAttr;

    private $perunUserAupAttr;

    private $perunVoAupAttr;

    private $perunFacilityRequestedAupsAttr;

    private $perunFacilityVoShortNames;

    /**
     * @var Adapter
     */
    private $adapter;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (! isset($config[self::UID_ATTR])) {
            throw new Exception(
                'perun:ForceAup: missing mandatory configuration option \'' . self::UID_ATTR . '\'.'
            );
        }
        if (! isset($config[self::PERUN_AUPS_ATTR]) && ! isset($config[self::PERUN_VO_AUP_ATTR])) {
            throw new Exception(
                'perun:ForceAup: missing at least one of mandatory configuration options \''
                . self::PERUN_AUPS_ATTR . '\' or \'' . self::PERUN_VO_AUP_ATTR . '\'.'
            );
        }
        if (! isset($config[self::PERUN_USER_AUP_ATTR])) {
            throw new Exception(
                'perun:ForceAup: missing mandatory configuration option \'' . self::PERUN_USER_AUP_ATTR . '\'.'
            );
        }
        if (! isset($config[self::INTERFACE_PROPNAME])) {
            $config[self::INTERFACE_PROPNAME] = Adapter::RPC;
        }

        $this->uidAttr = (string) $config[self::UID_ATTR];
        $this->perunAupsAttr = isset($config[self::PERUN_AUPS_ATTR]) ?
            (string) $config[self::PERUN_AUPS_ATTR] : null;
        $this->perunVoAupAttr = isset($config[self::PERUN_VO_AUP_ATTR]) ?
            (string) $config[self::PERUN_VO_AUP_ATTR] : null;
        $this->perunUserAupAttr = (string) $config[self::PERUN_USER_AUP_ATTR];
        $interface = (string) $config[self::INTERFACE_PROPNAME];
        $this->adapter = Adapter::getInstance($interface);

        $this->perunFacilityRequestedAupsAttr = (string) $config[self::PERUN_FACILITY_REQ_AUPS_ATTR];
        $this->perunFacilityVoShortNames = (string) $config[self::PERUN_FACILITY_VO_SHORT_NAMES_ATTR];
    }

    /**
     * @param $request
     */
    public function process(&$request)
    {
        assert(is_array($request));

        if (isset($request['perun']['user'])) {
            /**
             * allow IDE hint whisperer
             *
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

            $requestedAups = [];
            $voShortNames = [];

            $facilityAttrValues = $this->adapter->getFacilityAttributesValues(
                $facility,
                [$this->perunFacilityRequestedAupsAttr, $this->perunFacilityVoShortNames]
            );

            if (isset($this->perunFacilityRequestedAupsAttr, $facilityAttrValues) &&
                is_array($facilityAttrValues[$this->perunFacilityRequestedAupsAttr])) {
                foreach (array_values($facilityAttrValues[$this->perunFacilityRequestedAupsAttr]) as $facilityAup) {
                    array_push($requestedAups, $facilityAup);
                }
            }

            if (isset($this->perunFacilityVoShortNames, $facilityAttrValues) &&
                is_array($facilityAttrValues[$this->perunFacilityVoShortNames])) {
                foreach (array_values($facilityAttrValues[$this->perunFacilityVoShortNames]) as $facilityVoShortName) {
                    array_push($voShortNames, $facilityVoShortName);
                }
            }

            if (empty($requestedAups) && empty($voShortNames)) {
                Logger::debug(
                    'Perun.ForceAup - No AUPs to be approved have been requested by facility with EntityId: ' .
                    $request['SPMetadata']['entityid']
                );
                return;
            }

            $userAups = $this->adapter->getUserAttributesValues(
                $user,
                [$this->perunUserAupAttr]
            )[$this->perunUserAupAttr];

            if ($userAups === null) {
                $userAups = [];
            }

            $perunAups = $this->getPerunAups();
            $voAups = $this->getVoAups($voShortNames);

            $aupsToBeApproved = $this->getAupsToBeApproved($perunAups, $voAups, $requestedAups, $userAups);
        } catch (\Exception $ex) {
            Logger::warning('perun:ForceAup - ' . $ex->getMessage());
            $aupsToBeApproved = [];
        }

        Logger::debug('perun:ForceAup - NewAups: ' . json_encode($aupsToBeApproved));

        if (! empty($aupsToBeApproved)) {
            $request[self::UID_ATTR] = $this->uidAttr;
            $request[self::PERUN_USER_AUP_ATTR] = $this->perunUserAupAttr;
            $request['newAups'] = $aupsToBeApproved;
            $id = State::saveState($request, 'perun:forceAup');
            $url = Module::getModuleURL('perun/force_aup_page.php');
            HTTP::redirectTrustedURL($url, [
                'StateId' => $id,
            ]);
        }
    }

    /**
     * @param array $aups
     * @return aup with the latest date
     */
    public function getLatestAup($aups)
    {
        if (empty($aups)) {
            return null;
        }
        $latestAup = $aups[0];
        $latestDate = self::parseDateTime($latestAup->date);
        foreach ($aups as $aup) {
            $aupDate = self::parseDateTime($aup->date);
            if ($latestDate < $aupDate) {
                $latestAup = $aup;
                $latestDate = $aupDate;
            }
        }
        return $latestAup;
    }

    /**
     * @param string[] $voShortNames
     * @return array
     */
    public function getVoAups($voShortNames)
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

    private function getPerunAups()
    {
        $perunAupsAttr = [];
        if ($this->perunAupsAttr !== null) {
            $perunAupsAttr = $this->adapter->getEntitylessAttribute($this->perunAupsAttr);
        }

        $perunAups = [];
        foreach ($perunAupsAttr as $key => $attr) {
            $perunAups[$key] = $attr['value'];
        }
        return $perunAups;
    }

    private function getAupsToBeApproved($perunAups, $voAups, $requestedAups, $userAups)
    {
        $perunAupsToBeApproved = [];
        if (! empty($perunAups)) {
            $perunAupsToBeApproved = $this->fillAupsToBeApproved($requestedAups, $perunAups, $userAups);
        }

        $voAupsToBeApproved = [];
        if (! empty($voAups)) {
            $voAupsToBeApproved = $this->fillAupsToBeApproved($requestedAups, $voAups, $userAups);
        }
        return $this->mergeAupsToBeApproved($perunAupsToBeApproved, $voAupsToBeApproved);
    }

    private function fillAupsToBeApproved($requestedAups, $aups, $userApprovedAups)
    {
        $aupsToBeApproved = [];
        foreach ($requestedAups as $requestedAup) {
            $aupsInJson = $aups[$requestedAup];
            if (empty($aupsInJson)) {
                continue;
            }
            $decodedAups = json_decode($aupsInJson);
            $latestAup = $this->getLatestAup($decodedAups);
            if ($latestAup === null) {
                continue;
            }

            if (! empty($userApprovedAups[$requestedAup])) {
                $userAupsList = json_decode($userApprovedAups[$requestedAup]);
                $userLatestAup = $this->getLatestAup($userAupsList);
                if ($userLatestAup !== null) {
                    $latestDate = self::parseDateTime($latestAup->date);
                    $userLatestDate = self::parseDateTime($userLatestAup->date);
                    if ($userLatestDate >= $latestDate) {
                        continue;
                    }
                }
            }
            $aupsToBeApproved[$requestedAup] = $latestAup;
        }
        return $aupsToBeApproved;
    }

    private function mergeAupsToBeApproved(array $perunAupsToBeApproved, array $voAupsToBeApproved)
    {
        $resultAups = $perunAupsToBeApproved;
        foreach ($voAupsToBeApproved as $aupKey => $voAup) {
            if (array_key_exists($aupKey, $resultAups)) {
                $voLatestDate = self::parseDateTime($voAup->date);
                $perunLatestDate = self::parseDateTime($perunAupsToBeApproved[$aupKey]->date);
                if ($voLatestDate >= $perunLatestDate) {
                    $resultAups[$aupKey] = $voLatestDate;
                } else {
                    $resultAups[$aupKey] = $perunLatestDate;
                }
            } else {
                $resultAups[$aupKey] = $voAup;
            }
        }
        return $resultAups;
    }

    /**
     * Parses datetime with format set in self::DATETIME_FORMAT. If parsing fails, value passed in $default will be
     * returned (or null if not provided)
     *
     * @param string $date to be parsed using self::DATETIME_FORMAT format
     * @param DateTime|null $default (optional) value to be returned in case of error
     * @return DateTime parsed datetime, or default value (null if not provided)
     */
    private function parseDateTime(string $date, DateTime $default = null): DateTime
    {
        $result = DateTime::createFromFormat(self::DATETIME_FORMAT, $date);
        if ($result === false) {
            $result = $default;
        }
        return $result;
    }
}
