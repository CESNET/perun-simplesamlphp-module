<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

use SimpleSAML\Configuration;
use SimpleSAML\Logger;

/**
 * Implementation of WarningConfiguration using config file as the source of warning attributes
 *
 * @package SimpleSAML\Module\perun\model
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 * @author Dominik Frantisek Bucik <bucik@ics.muni.cz>
 */
class WarningConfigurationConfig extends WarningConfiguration
{
    public function getSourceOfWarningAttributes(): Configuration
    {
        return WarningConfiguration::getConfig()->getConfigItem(self::SOURCE_TYPE_CONFIG);
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
                    Logger::info('perun:WarningConfigurationFile: warningType has invalid value, value set to INFO');
                    $this->type = 'INFO';
                }
            } else {
                $this->enabled = false;
                Logger::warning(
                    'perun:WarningConfigurationFile: ' .
                    "missing or invalid 'type' parameter in file with warning configuration"
                );
                return $this;
            }

            if (! in_array($this->type, $this->allowedTypes, true)) {
                Logger::info("perun:WarningConfigurationConfig: '" . self::TYPE .
                    "' has invalid value, value set to '" . self::WARNING_TYPE_INFO . "'");
                $this->type = self::WARNING_TYPE_INFO;
            }

            $this->title = $conf->getArray(WarningConfiguration::TITLE, []);
            if (empty($this->title)) {
                $this->enabled = false;
                Logger::warning(
                    'perun:WarningConfigurationConfig: ' .
                    'missing or invalid wayf.warning.title in ' . self::CONFIG_FILE_NAME
                );
                return $this;
            }

            $this->text = $conf->getArray(WarningConfiguration::TEXT, []);
            if (empty($this->text)) {
                $this->enabled = false;
                Logger::warning(
                    'perun:WarningConfigurationConfig: ' .
                    'missing or invalid wayf.warning.text in ' . self::CONFIG_FILE_NAME
                );
                return $this;
            }
        }
        return $this;
    }
}
