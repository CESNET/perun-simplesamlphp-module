<?php

use SimpleSAML\Module\perun\DatabaseConnector;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\ScriptsUtils;

$entityBody = file_get_contents('php://input');
$body = json_decode($entityBody, true);

if ($body === false) {
    Logger::error('Perun.getChallenge: Received invalid json.');
    http_response_code(400);
    exit;
}

if (empty($body['id'] || strlen($body['id']) > 30 || !ctype_print($body['id']))) {
    Logger::error('Perun.getChallenge: Invalid id');
    http_response_code(400);
    exit;
}

if (empty($body['scriptName']) || strlen($body['scriptName']) > 255 || !ctype_print($body['scriptName'])) {
    http_response_code(400);
    Logger::error('Perun.getChallenge: Invalid scriptName');
    exit;
}

$id = $body['id'];
$scriptName = $body['scriptName'];

const RANDOM_BYTES_LENGTH = 32;
const TABLE_NAME = 'scriptChallenges';

try {
    $challenge = hash('sha256', random_bytes(RANDOM_BYTES_LENGTH));
} catch (Exception $ex) {
    Logger::error('Perun.getChallenge: Error while generating a challenge');
    http_response_code(500);
    exit;
}

$databaseConnector = new DatabaseConnector();
$conn = $databaseConnector->getConnection();
$generateChallengeSucceeded = ScriptsUtils::generateChallenge($conn, $challenge, $id, $scriptName);
$conn->close();

if (!$generateChallengeSucceeded) {
    exit;
}

echo $challenge;
