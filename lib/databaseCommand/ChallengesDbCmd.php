<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\databaseCommand;

/**
 * @author Dominik Baranek <baranek@ics.muni.cz>
 */
class ChallengesDbCmd extends DatabaseCommand
{
    private const CHALLENGES_TABLE_NAME = 'scriptChallenges';

    private const ID_COLUMN = 'id';

    private const CHALLENGE_COLUMN = 'challenge';

    private const SCRIPT_COLUMN = 'script';

    private const DATE_COLUMN = 'date';

    public function __construct()
    {
        parent::__construct();
    }

    public function insertChallenge($challenge, $id, $scriptName): bool
    {
        $query = 'INSERT INTO ' . self::CHALLENGES_TABLE_NAME .
            ' (' . self::ID_COLUMN . ', ' . self::CHALLENGE_COLUMN . ', ' . self::SCRIPT_COLUMN . ') VALUES' .
            ' (:' . self::ID_COLUMN . ', :' . self::CHALLENGE_COLUMN . ', :' . self::SCRIPT_COLUMN . ')';

        $params = [
            self::ID_COLUMN => $id,
            self::CHALLENGE_COLUMN => $challenge,
            self::SCRIPT_COLUMN => $scriptName,
        ];

        return $this->write($query, $params);
    }

    public function readChallenge($id, $scriptName)
    {
        $query = 'SELECT challenge FROM ' . self::CHALLENGES_TABLE_NAME . ' WHERE ' .
            self::ID_COLUMN . ' = :' . self::ID_COLUMN . ' AND ' . self::SCRIPT_COLUMN . ' = :' . self::SCRIPT_COLUMN;

        $params = [
            self::ID_COLUMN => $id,
            self::SCRIPT_COLUMN => $scriptName,
        ];

        return $this->read($query, $params)
            ->fetchColumn();
    }

    public function deleteChallenge($id): bool
    {
        $query = 'DELETE FROM ' . self::CHALLENGES_TABLE_NAME . ' WHERE ' . self::ID_COLUMN . ' = :' . self::ID_COLUMN;

        $params = [
            self::ID_COLUMN => $id,
        ];

        return $this->write($query, $params);
    }

    public function deleteOldChallenges(): bool
    {
        $query = 'DELETE FROM ' . self::CHALLENGES_TABLE_NAME . ' WHERE '
            . self::DATE_COLUMN . ' < (NOW() - INTERVAL 5 MINUTE)';

        $params = [];

        return $this->write($query, $params);
    }
}
