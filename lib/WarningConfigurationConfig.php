<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Configuration;

/**
 * Implementation of WarningConfiguration using config file as the source of warning attributes
 * @package SimpleSAML\Module\perun
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
class WarningConfigurationConfig extends WarningConfiguration
{
    public function getSourceOfWarningAttributes()
    {
        $config = Configuration::getConfig(WarningConfiguration::CONFIG_FILE_NAME);
        return $config;
    }

    public function getWarningAttributes()
    {
        $data = self::getSourceOfWarningAttributes();

        if ($data !== null) {
            $this->warningIsOn = $data->getBoolean(WarningConfiguration::WARNING_IS_ON, false);
        }

        if ($this->warningIsOn) {
            $this->warningUserCanContinue = $data->getBoolean(WarningConfiguration::WARNING_USER_CAN_CONTINUE, true);

            try {
                $this->warningTitle = $data->getString(WarningConfiguration::WARNING_TITLE);
                $this->warningText = $data->getString(WarningConfiguration::WARNING_TEXT);
                if (empty($this->warningTitle) || empty($this->warningText)) {
                    throw new Exception();
                }
            } catch (Exception $ex) {
                Logger::warning(
                    "perun:WarningConfigurationConfig: " .
                    "missing or invalid disco.warning.title or disco.warning.text in module_perun.php"
                );
                $this->warningIsOn = false;
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
