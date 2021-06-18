<?php

use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\ChallengeManager;

$entityBody = file_get_contents('php://input');
$body = json_decode($entityBody, true);

if ($body === false) {
    Logger::error('Perun.getChallenge: Received invalid json.');
    http_response_code(400);
    exit;
}

if (empty($body['id']) || strlen($body['id']) > 30 || !ctype_print($body['id'])) {
    Logger::error('Perun.getChallenge: Invalid id');
    http_response_code(400);
    exit;
}

if (empty($body['scriptName']) || strlen($body['scriptName']) > 255 || !ctype_print($body['scriptName'])) {
    http_response_code(400);
    Logger::error('Perun.getChallenge: Invalid scriptName');
    exit;
}

const CONFIG_FILE_NAME = 'challenges_config.php';
const HASH_ALG = 'hashAlg';
const CHALLENGE_LENGTH = 'challengeLength';

$id = $body['id'];
$scriptName = $body['scriptName'];

$config = Configuration::getConfig(CONFIG_FILE_NAME);
$hashAlg = $config->getString(HASH_ALG, 'sha512');
$challengeLength = $config->getInteger(CHALLENGE_LENGTH, 32);

try {
    $challenge = hash($hashAlg, random_bytes($challengeLength));
} catch (Exception $ex) {
    Logger::error('Perun.getChallenge: Error while generating a challenge');
    http_response_code(500);
    exit;
}

$challengeManager = new ChallengeManager();
$generateChallengeSucceeded = $challengeManager->insertChallenge($challenge, $id, $scriptName);

if (!$generateChallengeSucceeded) {
    exit;
}

echo $challenge;
