<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;

/**
 * Abstract class sspmod_perun_StatusConnector
 * specify interface to get status information about some components
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
abstract class StatusConnector
{

    const NAGIOS = 'NAGIOS';

    const CONFIG_FILE_NAME = 'module_perun.php';
    const STATUS_TYPE = 'status.type';

    protected $configuration;

    /**
     * StatusConnector constructor.
     */
    public function __construct()
    {
        $this->configuration = Configuration::getConfig(self::CONFIG_FILE_NAME);
    }

    /**
     * @return StatusConnector instance
     * @throws Exception thrown if interface does not match any supported interface
     */
    public static function getInstance()
    {
        $configuration = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $statusType = $configuration->getString(self::STATUS_TYPE, 'NAGIOS');
        if ($statusType === self::NAGIOS) {
            return new NagiosStatusConnector();
        } else {
            throw new Exception(
                'Unknown StatusConnector type in option \'' . self::STATUS_TYPE . '\'. Only ' .
                self::NAGIOS . ' type available now!'
            );
        }
    }

    /**
     * Returns list of components with statuses in this format:
     * [
     *      [
     *          'name' => 'Component name',
     *          'status' => 'Component status'
     *      ],
     * ],
     *
     * @return array
     */
    abstract public function getStatus();

    /**
     * Returns the HTML code with correct class
     * @param $status String Status of services
     *
     * @return string
     */
    public static function getBadgeByStatus($status)
    {
        if ($status === OK) {
            return '<span class="status label label-success">OK</span>';
        } elseif ($status === WARNING) {
            return '<span class="status label label-warning">WARNING</span>';
        } elseif ($status === CRITICAL || $status === UNKNOWN) {
            return '<span class="status label label-danger">CRITICAL</span>';
        }
    }
}
