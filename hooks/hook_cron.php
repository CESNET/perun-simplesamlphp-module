<?php

declare(strict_types=1);

use SimpleSAML\Logger;
use SimpleSAML\Module\perun\databaseCommand\ChallengesDbCmd;

/**
 * Hook to run a cron job.
 *
 * @param array $croninfo  Output
 * @author Dominik Baranek <baranek@ics.muni.cz>
 */
function perun_hook_cron(&$croninfo)
{
    if ($croninfo['tag'] !== 'hourly') {
        Logger::debug('cron [perun]: Skipping cron in cron tag [' . $croninfo['tag'] . '] ');
        return;
    }
    Logger::info('cron [perun]: Running cron in cron tag [' . $croninfo['tag'] . '] ');

    $challengesDbCmd = new ChallengesDbCmd();

    if (! $challengesDbCmd->deleteOldChallenges()) {
        Logger::error('cron [perun]: Error while deleting old challenges from the database.');
    }
}
