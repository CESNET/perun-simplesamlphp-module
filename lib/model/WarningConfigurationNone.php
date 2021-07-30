<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

use SimpleSAML\Configuration;

/**
 * Implementation of WarningConfiguration for no warning configured
 *
 * @package SimpleSAML\Module\perun\model
 * @author Dominik Frantisek Bucik <bucik@ics.muni.cz>
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
