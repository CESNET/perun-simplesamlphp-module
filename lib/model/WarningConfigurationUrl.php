<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Configuration;

/**
 * Implementation of WarningConfiguration using json url as the source of warning attributes
 * @package SimpleSAML\Module\perun
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
class WarningConfigurationUrl extends WarningConfiguration
{
    public function getSourceOfWarningAttributes(): Configuration
    {
        $url = null;
        $attributes = null;

        $config = Configuration::getConfig(WarningConfiguration::CONFIG_FILE_NAME);

        try {
            $url = $config->getString(WarningConfiguration::SOURCE_TYPE_URL);

            set_error_handler(function () {
                Logger::warning(
                    "perun:WarningConfigurationUrl: " .
                    "missing or invalid wayf.warning.url parameter in " . self::CONFIG_FILE_NAME
                );
            });

            $json_data = file_get_contents($url);
            restore_error_handler();
            $attributes = json_decode($json_data, true);
        } catch (\Exception) {
            Logger::warning(
                "perun:WarningConfigurationUrl: missing or invalid wayf.warning.url parameter in "
                . self::CONFIG_FILE_NAME
            );
        }

        return Configuration::loadFromArray($attributes);
    }

    public function getWarningAttributes(): WarningConfiguration
    {
        $conf = self::getSourceOfWarningAttributes();

        if ($conf !== null) {
            $this->enabled = $conf->getBoolean(WarningConfiguration::ENABLED, false);
        }

        if ($this->enabled) {
            if ($conf->hasValue(WarningConfiguration::TYPE)) {
                $this->type = $conf->getString(WarningConfiguration::TYPE, self::WARNING_TYPE_INFO);
                if (!in_array($this->type, $this->allowedTypes)) {
                    Logger::info("perun:WarningConfigurationUrl: '" . self::TYPE
                        . "' has invalid value, value set to " . self::WARNING_TYPE_INFO);
                    $this->type = self::WARNING_TYPE_INFO;
                }
            } else {
                $this->enabled = false;
                Logger::warning(
                    "perun:WarningConfigurationUrl: " .
                    "missing or invalid '" . self::TYPE . "' parameter in the URL content with warning configuration"
                );
                return $this;
            }

            $this->title = $conf->getString(WarningConfiguration::TITLE, '');
            if (empty($this->title)) {
                $this->enabled = false;
                Logger::warning(
                    "perun:WarningConfigurationUrl: " .
                    "missing or invalid '" . self::TITLE . "' parameter in the URL content with warning configuration"
                );
                return $this;
            }

            $this->text = $conf->getString(WarningConfiguration::TEXT, '');
            if (empty($this->text)) {
                $this->enabled = false;
                Logger::warning(
                    "perun:WarningConfigurationUrl: " .
                    "missing or invalid '" . self::TEXT . "' parameter in the URL content with warning configuration"
                );
                return $this;
            }
        }

        return $this;
    }

}
