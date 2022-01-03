<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;

/**
 * Abstract class StatusConnector specify interface to get status information about some components.
 */
abstract class StatusConnector
{
    public const NAGIOS = 'NAGIOS';

    public const CONFIG_FILE_NAME = 'module_perun.php';

    public const STATUS_TYPE = 'status.type';

    public const OK = 0;

    public const WARNING = 1;

    protected $configuration;

    public function __construct()
    {
        $this->configuration = Configuration::getConfig(self::CONFIG_FILE_NAME);
    }

    /**
     * @return StatusConnector instance
     */
    public static function getInstance()
    {
        $configuration = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $statusType = $configuration->getString(self::STATUS_TYPE, 'NAGIOS');
        if (self::NAGIOS === $statusType) {
            return new NagiosStatusConnector();
        }
        throw new Exception(
            'Unknown StatusConnector type in option \'' . self::STATUS_TYPE . '\'. Only ' . self::NAGIOS . ' type available now!'
        );
    }

    /**
     * Returns list of components with statuses in this format: [ [ 'name' => 'Component name', 'status' => 'Component
     * status' ], ],.
     *
     * @return array
     */
    abstract public function getStatus();

    /**
     * Returns the HTML code with correct class.
     *
     * @param string $status Status of services
     *
     * @return string
     */
    public static function getBadgeByStatus($status)
    {
        $statusAsInt = intval($status);

        if (self::OK === $statusAsInt) {
            return '<span class="status label label-success">OK</span>';
        }
        if (self::WARNING === $statusAsInt) {
            return '<span class="status label label-warning">WARNING</span>';
        }

        return '<span class="status label label-danger">CRITICAL</span>';
    }
}
