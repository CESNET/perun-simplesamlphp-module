<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

use SimpleSAML\Configuration;

/**
 * Implementation of WarningConfiguration for no warning configured
 */
class WarningConfigurationNone extends WarningConfiguration
{
    public function getSourceOfWarningAttributes(): Configuration
    {
        return WarningConfiguration::getConfig();
    }

    public function getWarningAttributes(): WarningConfiguration
    {
        return $this;
    }
}
