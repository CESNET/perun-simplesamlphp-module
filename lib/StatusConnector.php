<?php


/**
 * Abstract class sspmod_perun_StatusConnector
 * specify interface to get status information about some components
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
abstract class sspmod_perun_StatusConnector
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
        $this->configuration = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
    }

    /**
     * @return sspmod_perun_StatusConnector instance
     * @throws SimpleSAML_Error_Exception thrown if interface does not match any supported interface
     */
    public static function getInstance() {
        $configuration = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
        $statusType = $configuration->getString(self::STATUS_TYPE, "NAGIOS");
        if ($statusType === self::NAGIOS) {
            return new sspmod_perun_NagiosStatusConnector();
        } else {
            throw new SimpleSAML_Error_Exception("Unknown StatusConnector type in option '" . self::STATUS_TYPE . "'. Only " .
                                                 self::NAGIOS . " type available now!");
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
    public abstract function getStatus();

}
