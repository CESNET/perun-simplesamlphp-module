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

    const CONFIG_FILE_NAME = "module_perun.php";
    const STATUS_TYPE = "status.type";

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
        $statusType = $configuration->getString(self::STATUS_TYPE, "NAGIOS");
        if ($statusType === self::NAGIOS) {
            return new NagiosStatusConnector();
        } else {
            throw new Exception(
                "Unknown StatusConnector type in option '" . self::STATUS_TYPE . "'. Only " .
                self::NAGIOS . " type available now!"
            );
        }
    }

    /**
     * Returns list of components with statuses in this format:
     * array(
     *      array(
     *          'name' => 'Component name',
     *          'status' => 'Component status'
     *      ),
     * ),
     *
     * @return array
     */
    abstract public function getStatus();
}
