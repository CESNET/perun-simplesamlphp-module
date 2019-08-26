<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Configuration;


/**
 * Class WarningConfiguration provides an option to load warning in disco-tpl from different types of sources
 *
 * @package SimpleSAML\Module\perun
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
abstract class WarningConfiguration
{
    const CONFIG_FILE_NAME = 'module_perun.php';
    const WARNING_SOURCE = 'disco.warning.source';

    const WARNING_FILE = 'disco.warning.file';
    const WARNING_URL = 'disco.warning.url';
    const WARNING_TYPE = 'disco.warning.type';
    const WARNING_IS_ON = 'disco.warning.isOn';
    const WARNING_TITLE = 'disco.warning.title';
    const WARNING_TEXT = 'disco.warning.text';

    const WARNING_TYPE_INFO = 'INFO';
    const WARNING_TYPE_WARNING = 'WARNING';
    const WARNING_TYPE_ERROR = 'ERROR';

    protected $warningIsOn = false;
    protected $warningType = '';
    protected $warningTitle = '';
    protected $warningText = '';

    protected $allowedTypes = [self::WARNING_TYPE_INFO, self::WARNING_TYPE_WARNING, self::WARNING_TYPE_ERROR];

    /**
     * Function returns the instance of WarningConfiguration
     * @return WarningConfigurationConfig|WarningConfigurationFile|WarningConfigurationUrl
     */
    public static function getInstance()
    {
        $configuration = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $source = $configuration->getString(self::WARNING_SOURCE);
        if ($source === 'CONFIG') {
            return new WarningConfigurationConfig();
        } elseif ($source === 'FILE') {
            return new WarningConfigurationFile();
        } elseif ($source === 'URL') {
            return new WarningConfigurationUrl();
        } else {
            Logger::warning("perun:WarningConfiguration: missing or invalid disco.warning.source in module_perun.php");
            throw new Exception(
                "perun:WarningConfiguration: missing or invalid disco.warning.source in module_perun.php"
            );
        }
    }

    /**
     * @return string data with warning attributes
     */
    abstract public function getSourceOfWarningAttributes();

    /**
     * @return array with warning attributes
     */
    abstract public function getWarningAttributes();
}
