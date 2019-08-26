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
    public function getSourceOfWarningAttributes()
    {
        $url = null;
        $attributes = null;

        $config = Configuration::getConfig(WarningConfiguration::CONFIG_FILE_NAME);

        try {
            $url = $config->getString(WarningConfiguration::WARNING_URL);

            set_error_handler(function () {
                Logger::warning(
                    "perun:WarningConfigurationUrl: " .
                    "missing or invalid disco.warning.url parameter in module_perun.php"
                );
            });

            $json_data = file_get_contents($url);
            restore_error_handler();
            $attributes = json_decode($json_data, true);
        } catch (\Exception $ex) {
            Logger::warning(
                "perun:WarningConfigurationUrl: missing or invalid disco.warning.url parameter in module_perun.php"
            );
        }

        return $attributes;
    }

    public function getWarningAttributes()
    {
        $data = self::getSourceOfWarningAttributes();

        if ($data !== null) {
            if (isset($data[WarningConfiguration::WARNING_IS_ON])) {
                $this->warningIsOn = $data[WarningConfiguration::WARNING_IS_ON];
            } else {
                Logger::warning(
                    "perun:WarningConfigurationUrl: " .
                    "missing or invalid warningIsOn parameter in file with warning configuration"
                );
            }

            if (isset($data[WarningConfiguration::WARNING_TYPE])) {
                $this->warningType = $data[WarningConfiguration::WARNING_TYPE];

                if (!in_array($this->warningType, $this->allowedTypes)) {
                    Logger::info('perun:warningConfigurationUrl: warningType has invalid value, value set to INFO');
                    $this->warningType = 'INFO';
                }
            } else {
                Logger::warning(
                    "perun:WarningConfigurationUrl: " .
                    "missing or invalid warningType parameter in file with warning configuration"
                );
            }

            if (isset($data[WarningConfiguration::WARNING_TITLE])) {
                $this->warningTitle = $data[WarningConfiguration::WARNING_TITLE];
            } else {
                Logger::warning(
                    "perun:WarningConfigurationUrl: " .
                    "missing or invalid warningTitle parameter in file with warning configuration"
                );
            }

            if (isset($data[WarningConfiguration::WARNING_TEXT])) {
                $this->warningText = $data[WarningConfiguration::WARNING_TEXT];
            } else {
                Logger::warning(
                    "perun:warningConfigurationUrl: " .
                    "missing or invalid warningText parameter in file with warning configuration"
                );
            }
        }

        return array(
            'warningIsOn' => $this->warningIsOn,
            'warningType' => $this->warningType,
            'warningTitle' => $this->warningTitle,
            'warningText' => $this->warningText
        );
    }
}
