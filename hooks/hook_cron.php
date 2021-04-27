<?php

use SimpleSAML\Logger;
use SimpleSAML\Module\perun\DatabaseConnector;

const TABLE_NAME = 'scriptChallenges';
const DATE_COLUMN = 'date';

/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 * @author Dominik Baranek <baranek@ics.muni.cz>
 */
function challenges_hook_cron(&$croninfo)
{
    if ($croninfo['tag'] !== 'hourly') {
        Logger::debug('cron [perun]: Skipping cron in cron tag [' . $croninfo['tag'] . '] ');
        return;
    }

    Logger::info('cron [perun]: Running cron in cron tag [' . $croninfo['tag'] . '] ');

    try {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();

        if ($conn !== null) {
            $stmt = $conn->prepare(
                'DELETE FROM ' . TABLE_NAME . ' WHERE ' . DATE_COLUMN . ' < (NOW() - INTERVAL 5 MINUTE)'
            );

            if (!$stmt) {
                $conn->close();
                Logger::error('cron [perun]: Error during preparing statement');
                return;
            }

            $ex = $stmt->execute();

            if ($ex === false) {
                Logger::error('cron [perun]: Error while deleting old challenges from the database.');
            }

            $stmt->close();
            $conn->close();
        }
    } catch (\Exception $e) {
        $croninfo['summary'][] = 'Error while deleting old challenges from the database: ' . $e->getMessage();
    }
}
