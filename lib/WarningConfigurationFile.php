<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Configuration;

/**
 * Implementation of WarningConfiguration using json file as the source of warning attributes
 * @package SimpleSAML\Module\perun
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
class WarningConfigurationFile extends WarningConfiguration
{
    public function getSourceOfWarningAttributes()
    {
        $file = null;
        $data = null;

        $config = Configuration::getConfig(WarningConfiguration::CONFIG_FILE_NAME);

        try {
            $file = $config->getString(WarningConfiguration::WARNING_FILE);

            set_error_handler(function () {
                Logger::warning(
                    "perun:WarningConfigurationFile: " .
                    "missing or invalid disco.warning.file parameter in module_perun.php"
                );
            });

            $json_data = file_get_contents($file);
            restore_error_handler();
            $data = json_decode($json_data, true);
        } catch (\Exception $ex) {
            Logger::warning(
                "perun:WarningConfigurationFile: missing or invalid disco.warning.file parameter in module_perun.php"
            );
        }

        return $data;
    }

    public function getWarningAttributes()
    {
        $data = self::getSourceOfWarningAttributes();

        if ($data !== null) {
            if (isset($data[WarningConfiguration::WARNING_IS_ON])) {
                $this->warningIsOn = $data[WarningConfiguration::WARNING_IS_ON];
            } else {
                Logger::warning(
                    "perun:warningConfigurationFile: " .
                    "missing or invalid warningIsOn parameter in file with warning configuration"
                );
            }

            if (isset($data[WarningConfiguration::WARNING_USER_CAN_CONTINUE])) {
                $this->warningUserCanContinue = $data[WarningConfiguration::WARNING_USER_CAN_CONTINUE];
            } else {
                Logger::warning(
                    "perun:warningConfigurationFile: " .
                    "missing or invalid warningUserCanContinue parameter in file with warning configuration"
                );
            }

            if (isset($data[WarningConfiguration::WARNING_TITLE])) {
                $this->warningTitle = $data[WarningConfiguration::WARNING_TITLE];
            } else {
                Logger::warning(
                    "perun:warningConfigurationFile: " .
                    "missing or invalid warningTitle parameter in file with warning configuration"
                );
            }

            if (isset($data[WarningConfiguration::WARNING_TEXT])) {
                $this->warningText = $data[WarningConfiguration::WARNING_TEXT];
            } else {
                Logger::warning(
                    "perun:warningConfigurationFile: " .
                    "missing or invalid warningText parameter in file with warning configuration"
                );
            }
        }

        return array(
            'warningIsOn' => $this->warningIsOn,
            'warningUserCanContinue' => $this->warningUserCanContinue,
            'warningTitle' => $this->warningTitle,
            'warningText' => $this->warningText
        );
    }
}
