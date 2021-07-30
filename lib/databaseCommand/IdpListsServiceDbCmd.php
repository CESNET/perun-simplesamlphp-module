<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\databaseCommand;

use PDO;
use SimpleSAML\Logger;

/**
 * @author Dominik Baranek <baranek@ics.muni.cz>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class IdpListsServiceDbCmd extends DatabaseCommand
{
    public const WHITELIST = 'whiteList';

    public const GREYLIST = 'greyList';

    public const ENTITY_ID_COLUMN = 'entityId';

    public const REASON_COLUMN = 'reason';

    public const LOG_PREFIX = 'perun:IdpListsServiceDbCmd: ';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Function returns array of all IdPs in whitelist/greylist
     *
     * @param string $tableName 'whitelist' or 'greylist'
     * @return array of all IdPs, every IdP is represents as array
     */
    public function getAllIdps($tableName)
    {
        $whiteListTableName = $this->config->getWhitelistTableName();
        $greyListTableName = $this->config->getGreyListTableName();
        $table = null;

        if ($tableName === self::WHITELIST) {
            $table = $whiteListTableName;
        } elseif ($tableName === self::GREYLIST) {
            $table = $greyListTableName;
        }

        $query = 'SELECT * FROM ' . $table;
        $params = [];

        return $this->read($query, $params)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Function returns array of all entityId in whitelist/greylist
     *
     * @param string $tableName 'whitelist' or 'greylist'
     * @return array of entityIds
     */
    public function getAllEntityIds($tableName)
    {
        $whiteListTableName = $this->config->getWhitelistTableName();
        $greyListTableName = $this->config->getGreyListTableName();
        $table = null;

        if ($tableName === self::WHITELIST) {
            $table = $whiteListTableName;
        } elseif ($tableName === self::GREYLIST) {
            $table = $greyListTableName;
        }

        $query = 'SELECT ' . self::ENTITY_ID_COLUMN . ' FROM ' . $table;
        $params = [];

        return $this->read($query, $params)
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Function inserts the line into table with $tableName
     *
     * @param string $tableName 'whitelist' or 'greylist'
     * @param string $entityId
     * @param string $reason
     */
    public function insertToList($tableName, $entityId, $reason)
    {
        $whiteListTableName = $this->config->getWhitelistTableName();
        $greyListTableName = $this->config->getGreyListTableName();
        $table = null;

        if ($tableName === self::WHITELIST) {
            $table = $whiteListTableName;
        } elseif ($tableName === self::GREYLIST) {
            $table = $greyListTableName;
        }

        $query = 'INSERT INTO ' . $table .
            ' (' . self::ENTITY_ID_COLUMN . ', ' . self::REASON_COLUMN . ') VALUES' .
            ' (:' . self::ENTITY_ID_COLUMN . ', :' . self::REASON_COLUMN . ')';

        $params = [
            self::ENTITY_ID_COLUMN => $entityId,
            self::REASON_COLUMN => $reason,
        ];

        if (! $this->write($query, $params)) {
            Logger::error(self::LOG_PREFIX . 'Error while inserting into the database.');
        }
    }

    /**
     * Function deletes the line from table with $tableName and $entityID
     *
     * @param string $tableName 'whitelist' or 'greylist'
     * @param string $entityId
     */
    public function deleteFromList($tableName, $entityId)
    {
        $whiteListTableName = $this->config->getWhitelistTableName();
        $greyListTableName = $this->config->getGreyListTableName();
        $table = null;

        if ($tableName === self::WHITELIST) {
            $table = $whiteListTableName;
        } elseif ($tableName === self::GREYLIST) {
            $table = $greyListTableName;
        }

        $query = 'DELETE FROM ' . $table . ' WHERE ' . self::ENTITY_ID_COLUMN . ' = :' . self::ENTITY_ID_COLUMN;

        $params = [
            self::ENTITY_ID_COLUMN => $entityId,
        ];

        if (! $this->write($query, $params)) {
            Logger::error(self::LOG_PREFIX . 'Error while deleting from the database.');
        }
    }
}
