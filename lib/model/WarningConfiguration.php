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

    const WARNING = 'warning';
    const SOURCE = "source";
    const SOURCE_TYPE_FILE = 'file';
    const SOURCE_TYPE_URL = 'url';
    const SOURCE_TYPE_CONFIG = 'config';
    const TYPE = 'type';
    const ENABLED = 'enabled';
    const TITLE = 'title';
    const TEXT = 'text';

    const WARNING_TYPE_INFO = 'INFO';
    const WARNING_TYPE_WARNING = 'WARNING';
    const WARNING_TYPE_ERROR = 'ERROR';

    protected bool $enabled = false;
    protected string $type = '';
    protected string $title = '';
    protected string $text = '';

    protected array $allowedTypes = [self::WARNING_TYPE_INFO, self::WARNING_TYPE_WARNING, self::WARNING_TYPE_ERROR];

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Function returns the instance of WarningConfiguration
     * @return WarningConfigurationConfig|WarningConfigurationFile|WarningConfigurationUrl
     */
    public static function getInstance(): WarningConfigurationConfig|WarningConfigurationUrl|WarningConfigurationFile
    {
        $configuration = WarningConfiguration::getConfig();
        $source = strtolower($configuration->getString(self::SOURCE));
        if ($source === self::SOURCE_TYPE_CONFIG) {
            return new WarningConfigurationConfig();
        } elseif ($source === self::SOURCE_TYPE_FILE) {
            return new WarningConfigurationFile();
        } elseif ($source === self::SOURCE_TYPE_URL) {
            return new WarningConfigurationUrl();
        } else {
            Logger::warning("perun:WarningConfiguration: missing or invalid wayf.warning.source in module_perun.php");
            throw new Exception(
                "perun:WarningConfiguration: missing or invalid wayf.warning.source in module_perun.php"
            );
        }
    }

    public static function getConfig(): Configuration {
        return Configuration::getConfig(self::CONFIG_FILE_NAME)
            ->getConfigItem(Disco::WAYF)
            ->getConfigItem(WarningConfiguration::WARNING);
    }

    /**
     * @return Configuration data with warning attributes
     */
    abstract public function getSourceOfWarningAttributes(): Configuration;

    /**
     * @return WarningConfiguration with warning attributes
     */
    abstract public function getWarningAttributes(): WarningConfiguration;

}
