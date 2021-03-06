<?php

namespace SimpleSAML\Module\perun\databaseCommand;

use SimpleSAML\Database;

/**
 * @author Dominik Baranek <baranek@ics.muni.cz>
 */
abstract class DatabaseCommand
{
    protected $config;

    private $conn;

    public function __construct()
    {
        $this->config = DatabaseConfig::getInstance();
        $this->conn = Database::getInstance($this->config->getStore());
    }

    protected function read($query, $params)
    {
        return $this->conn->read($query, $params);
    }

    protected function write($query, $params): bool
    {
        return $this->conn->write($query, $params);
    }
}
