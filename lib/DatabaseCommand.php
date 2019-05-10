<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Logger;

/**
 * Class for working with Database
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class DatabaseCommand
{

    const WHITELIST = "whiteList";
    const GREYLIST = "greyList";
    /**
     * Function returns array of all IdPs in whitelist/greylist
     * @param string $tableName 'whitelist' or 'greylist'
     * @return array of all IdPs, every IdP is represents as array
     */
    public static function getAllIdps($tableName)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $whiteListTableName = $databaseConnector->getWhiteListTableName();
        $greyListTableName = $databaseConnector->getGreyListTableName();
        $table = null;
        $listOfIdPs = [];
        assert($conn != null);

        if ($tableName == self::WHITELIST) {
            $table = $whiteListTableName;
        } elseif ($tableName == self::GREYLIST) {
            $table = $greyListTableName;
        }

        $stmt = $conn->prepare("SELECT * FROM " . $table);

        if ($stmt) {
            $ex = $stmt->execute();
            if ($ex === false) {
                Logger::error("Error during select all from " . $table);
            }

            $stmt->bind_result($timestamp, $entityId, $reason);
            while ($stmt->fetch()) {
                $idp = [];
                $idp['timestamp'] = $timestamp;
                $idp['entityid'] = $entityId;
                $idp['reason'] = $reason;
                array_push($listOfIdPs, $idp);
            }

            $stmt->close();
        } else {
            Logger::error("Error during preparing statement");
        }

        $conn->close();
        return $listOfIdPs;
    }

    /**
     * Function returns array of all entityId in whitelist/greylist
     * @param string $tableName 'whitelist' or 'greylist'
     * @return array of entityIds
     */
    public static function getAllEntityIds($tableName)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $whiteListTableName = $databaseConnector->getWhiteListTableName();
        $greyListTableName = $databaseConnector->getGreyListTableName();
        $table = null;
        $listOfIdPs = [];
        assert($conn != null);

        if ($tableName == self::WHITELIST) {
            $table = $whiteListTableName;
        } elseif ($tableName == self::GREYLIST) {
            $table = $greyListTableName;
        }

        $stmt = $conn->prepare("SELECT * FROM " . $table);

        if ($stmt) {
            $ex = $stmt->execute();
            if ($ex === false) {
                Logger::error("Error during select all entityIds from " . $table);
            }

            $stmt->bind_result($timestamp, $entityId, $reason);
            while ($stmt->fetch()) {
                array_push($listOfIdPs, $entityId);
            }

            $stmt->close();
        } else {
            Logger::error("Error during preparing statement");
        }

        $conn->close();
        return $listOfIdPs;
    }

    /**
     * Function inserts the line into table with $tableName
     * @param string $tableName 'whitelist' or 'greylist'
     * @param string $entityId
     * @param string $reason
     */
    public static function insertTolist($tableName, $entityId, $reason)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $whiteListTableName = $databaseConnector->getWhiteListTableName();
        $greyListTableName = $databaseConnector->getGreyListTableName();
        $table = null;
        assert($conn != null);

        if ($tableName == self::WHITELIST) {
            $table = $whiteListTableName;
        } elseif ($tableName == self::GREYLIST) {
            $table = $greyListTableName;
        }

        $stmt = $conn->prepare("INSERT INTO " . $table . " (entityId, reason) VALUES (?, ?)");

        if ($stmt) {
            $stmt->bind_param("ss", $entityId, $reason);
            $ex = $stmt->execute();
            if ($ex === false) {
                Logger::error("Error during inserting entityId " . $entityId . " into " . $table);
            }

            Logger::debug("EntityId " . $entityId . " was inserted into " . $table);
            $stmt->close();
        } else {
            Logger::error("Error during preparing statement");
        }

        $conn->close();
    }

    /**
     * Function deletes the line from table with $tableName and $entityID
     * @param string $tableName 'whitelist' or 'greylist'
     * @param string $entityId
     */
    public static function deleteFromList($tableName, $entityId)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $whiteListTableName = $databaseConnector->getWhiteListTableName();
        $greyListTableName = $databaseConnector->getGreyListTableName();
        $table = null;
        assert($conn != null);

        if ($tableName == self::WHITELIST) {
            $table = $whiteListTableName;
        } elseif ($tableName == self::GREYLIST) {
            $table = $greyListTableName;
        }

        $stmt = $conn->prepare("DELETE FROM " . $table . " WHERE entityId=?");

        if ($stmt) {
            $stmt->bind_param("s", $entityId);
            $ex = $stmt->execute();
            if ($ex === false) {
                Logger::error("Error during deleting entityId " . $entityId . " from " . $table);
            }

            Logger::debug("EntityId " . $entityId . " was deleted from " . $table);
            $stmt->close();
        } else {
            Logger::error("Error during preparing statement");
        }

        $conn->close();
    }
}
