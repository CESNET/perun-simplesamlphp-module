<?php

namespace SimpleSAML\Module\perun\model;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Configuration;
use SimpleSAML\Module\perun\Disco;


/**
 * Class WarningConfiguration provides an option to load warning in disco-tpl from different types of sources
 *
 * @package SimpleSAML\Module\perun\Model
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
abstract class WarningConfiguration
{
    const CONFIG_FILE_NAME = 'module_perun.php';

    const WARNING = 'warning';
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
    protected array $title = [];
    protected array $text = [];

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
     * @return array
     */
    public function getTitle(): array
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function getText(): array
    {
        return $this->text;
    }

    /**
     * Function returns the instance of WarningConfiguration
     * @return WarningConfiguration
     * @throws Exception
     */
    public static function getInstance(): WarningConfiguration
    {
        $configuration = WarningConfiguration::getConfig();
        if ($configuration->hasValue(self::SOURCE_TYPE_CONFIG)) {
            return new WarningConfigurationConfig();
        } elseif ($configuration->hasValue(self::SOURCE_TYPE_FILE)) {
            return new WarningConfigurationFile();
        } elseif ($configuration->hasValue(self::SOURCE_TYPE_URL)) {
            return new WarningConfigurationUrl();
        } else {
            return new WarningConfigurationNone();
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
