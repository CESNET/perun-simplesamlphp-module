<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\databaseCommand;

use SimpleSAML\Configuration;

class DatabaseConfig
{
    private const CONFIG_FILE_NAME = 'module_perun.php';

    private const DATABASE = 'database';

    private const STORE = 'store';

    private const WHITELIST_TABLE_NAME = 'whiteListTableName';

    private const GREYLIST_TABLE_NAME = 'greyListTableName';

    private $config;

    private $store;

    private $whitelistTableName;

    private $greyListTableName;

    private static $instance = null;

    private function __construct()
    {
        $configuration = Configuration::getConfig(self::CONFIG_FILE_NAME);

        $this->config = $configuration->getConfigItem(self::DATABASE, null);
        $this->store = $this->config->getConfigItem(self::STORE, null);

        $this->whitelistTableName = $this->config->getString(self::WHITELIST_TABLE_NAME, null);
        $this->greyListTableName = $this->config->getString(self::GREYLIST_TABLE_NAME, null);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getStore()
    {
        return $this->store;
    }

    public function getWhitelistTableName()
    {
        return $this->whitelistTableName;
    }

    public function getGreyListTableName()
    {
        return $this->greyListTableName;
    }
}
