<?php


namespace SimpleSAML\Module\perun;


class ScriptsUtils
{
    const CHALLENGES_TABLE_NAME = 'scriptChallenges';
    const CHALLENGE = 'challenge';

    public static function generateChallenge($connection, $challenge, $id, $scriptName): bool
    {
        if ($connection === null || empty($challenge)) {
            Logger::error('Perun:ScriptsUtils: Error while creating a challenge');
            http_response_code(500);
            return false;
        }

        $stmt = $connection->prepare(
            'INSERT INTO ' . self::CHALLENGES_TABLE_NAME . ' (id, challenge, script) VALUES (?, ?, ?)'
        );

        if ($stmt) {
            $stmt->bind_param('sss', $id, $challenge, $scriptName);
            $ex = $stmt->execute();

            if ($ex === false) {
                Logger::error('Perun:ScriptsUtils: Error while creating a challenge');
                http_response_code(500);
                return false;
            }

            $stmt->close();
        } else {
            Logger::error('Perun:ScriptsUtils: Error during preparing statement');
            http_response_code(500);
            return false;
        }

        return true;
    }

    public static function readChallengeFromDb($connection, $id)
    {
        if ($connection === null) {
            http_response_code(500);
            return null;
        }

        if (empty($id)) {
            http_response_code(400);
            return null;
        }

        $stmt = $connection->prepare('SELECT challenge FROM ' . self::CHALLENGES_TABLE_NAME . ' WHERE id=?');

        if (!$stmt) {
            Logger::error('Perun:ScriptsUtils: Error during preparing statement');
            http_response_code(500);
            return null;
        }

        $stmt->bind_param('s', $id);
        $ex = $stmt->execute();

        if ($ex === false) {
            Logger::error('Perun:ScriptsUtils: Error while getting the challenge from the database.');
            http_response_code(500);
            return null;
        }

        $challengeDb = $stmt->get_result()->fetch_assoc()[self::CHALLENGE];
        $stmt->close();

        return $challengeDb;
    }

    public static function checkAccess($connection, $challenge, $challengeDb): bool
    {
        if ($connection === null) {
            http_response_code(500);
            return false;
        }

        if (empty($challenge) || empty($challengeDb)) {
            http_response_code(400);
            return false;
        }

        if (!hash_equals($challengeDb, $challenge)) {
            Logger::error('Perun:ScriptsUtils: Hashes are not equal.');
            http_response_code(401);
            return false;
        }

        return true;
    }

    public static function deleteChallengeFromDb($connection, $id): bool
    {
        if ($connection === null) {
            http_response_code(500);
            return false;
        }

        if (empty($id)) {
            http_response_code(400);
            return false;
        }

        $stmt = $connection->prepare('DELETE FROM ' . self::CHALLENGES_TABLE_NAME . ' WHERE id=?');

        if ($stmt) {
            $stmt->bind_param('s', $id);
            $ex = $stmt->execute();

            if ($ex === false) {
                Logger::error('Perun:ScriptsUtils: Error while deleting the challenge from the database.');
                http_response_code(500);
                return false;
            }

            $stmt->close();
        } else {
            Logger::error('Perun:ScriptsUtils: Error during preparing statement');
            http_response_code(500);
            return false;
        }

        return true;
    }
}
