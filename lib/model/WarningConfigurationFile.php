<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

use SimpleSAML\Configuration;
use SimpleSAML\Logger;

/**
 * Implementation of WarningConfiguration using json file as the source of warning attributes
 *
 * @package SimpleSAML\Module\perun\model
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
class WarningConfigurationFile extends WarningConfiguration
{
    public function getSourceOfWarningAttributes(): Configuration
    {
        $file = null;
        $data = null;

        $config = WarningConfiguration::getConfig();

        try {
            $file = $config->getString(WarningConfiguration::SOURCE_TYPE_FILE);

            set_error_handler(function () {
                Logger::warning(
                    'perun:WarningConfigurationFile: ' .
                    'missing or invalid wayf.warning.file parameter in ' . self::CONFIG_FILE_NAME
                );
            });

            $json_data = file_get_contents($file);
            restore_error_handler();
            $data = json_decode($json_data, true);
        } catch (\Exception $ex) {
            Logger::warning(
                'perun:WarningConfigurationFile: missing or invalid wayf.warning.file parameter in '
                . self::CONFIG_FILE_NAME
            );
        }

        return Configuration::loadFromArray($data);
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
                if (! in_array($this->type, $this->allowedTypes, true)) {
                    Logger::info("perun:WarningConfigurationFile: '" . self::TYPE
                        . "' has invalid value, value set to " . self::WARNING_TYPE_INFO);
                    $this->type = self::WARNING_TYPE_INFO;
                }
            } else {
                $this->enabled = false;
                Logger::warning(
                    'perun:WarningConfigurationFile: ' .
                    "missing or invalid '" . self::TYPE . "' parameter in file with warning configuration"
                );
                return $this;
            }

            $this->title = $conf->getArray(WarningConfiguration::TITLE, []);
            if (empty($this->title)) {
                $this->enabled = false;
                Logger::warning(
                    'perun:WarningConfigurationFile: ' .
                    "missing or invalid '" . self::TITLE . "' parameter in file with warning configuration"
                );
                return $this;
            }

            $this->text = $conf->getArray(WarningConfiguration::TEXT, []);
            if (empty($this->text)) {
                $this->enabled = false;
                Logger::warning(
                    'perun:WarningConfigurationFile: ' .
                    "missing or invalid '" . self::TEXT . "' parameter in file with warning configuration"
                );
                return $this;
            }
        }

        return $this;
    }
}
