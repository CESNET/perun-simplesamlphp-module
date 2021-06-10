<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Logger;
use SimpleSAML\Module\perun\databaseCommand\ChallengesDbCmd;

class ChallengeManager
{
    const LOG_PREFIX = 'Perun:ChallengeManager: ';
    private $challengeDbCmd;

    public function __construct()
    {
        $this->challengeDbCmd = new ChallengesDbCmd();
    }

    public function insertChallenge($challenge, $id, $scriptName): bool
    {
        if (empty($challenge) ||
            empty($id) ||
            empty($scriptName) ||
            !$this->challengeDbCmd->insertChallenge($challenge, $id, $scriptName)) {
            Logger::error(self::LOG_PREFIX . 'Error while creating a challenge');
            http_response_code(500);
            return false;
        }

        return true;
    }

    public function readChallengeFromDb($id)
    {
        if (empty($id)) {
            http_response_code(400);
            return null;
        }

        $result = $this->challengeDbCmd->readChallenge($id);

        if ($result === null) {
            http_response_code(500);
        }

        return $result;
    }

    public static function checkAccess($challenge, $challengeDb): bool
    {
        if (empty($challenge) || empty($challengeDb)) {
            http_response_code(400);
            return false;
        }

        if (!hash_equals($challengeDb, $challenge)) {
            Logger::error(self::LOG_PREFIX . 'Hashes are not equal.');
            http_response_code(401);
            return false;
        }

        return true;
    }

    public function deleteChallengeFromDb($id): bool
    {
        if (empty($id)) {
            http_response_code(400);
            return false;
        }

        if (!$this->challengeDbCmd->deleteChallenge($id)) {
            Logger::error(self::LOG_PREFIX . 'Error while deleting challenge from the database.');
            http_response_code(500);
            return false;
        }

        return true;
    }

    public static function getAlgorithm($path, $className)
    {
        $classPath = sprintf('Jose\\Component\\%s\\%s', $path, $className);
        if (! class_exists($classPath)) {
            throw new \Exception('Invalid algorithm specified: ' . $classPath);
        }
        return new $classPath();
    }
}
