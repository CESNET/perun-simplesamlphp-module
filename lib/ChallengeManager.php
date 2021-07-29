<?php

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\databaseCommand\ChallengesDbCmd;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Checker;

class ChallengeManager
{
    const LOG_PREFIX = 'Perun:ChallengeManager: ';

    const CONFIG_FILE_NAME = 'challenges_config.php';
    const HASH_ALG = 'hashAlg';
    const SIG_ALG = 'sigAlg';
    const CHALLENGE_LENGTH = 'challengeLength';
    const PUB_KEY = 'pubKey';
    const PRIV_KEY = 'privKey';

    private $challengeDbCmd;

    private $hashAlg;
    private $challengeLength;

    private $privKey;
    private $pubKey;


    public function __construct()
    {
        $this->challengeDbCmd = new ChallengesDbCmd();
        $config = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->hashAlg = $config->getString(self::HASH_ALG, 'sha512');
        $this->sighAlg = $config->getString(self::SIG_ALG, 'sha512');
        $this->challengeLength = $config->getInteger(self::CHALLENGE_LENGTH, 32);
        $this->pubKey = $config->getString(self::PUB_KEY);
        $this->privKey = $config->getString(self::PRIV_KEY);
    }

    public function generateChallenge($id, $scriptName): string
    {
        try {
            $challenge = hash($this->hashAlg, random_bytes($this->challengeLength));
        } catch (Exception $ex) {
            http_response_code(500);
            throw new Exception('ChallengeManager.generateChallenge: Error while generating a challenge');
        }

        if (empty($id) ||
            empty($scriptName) ||
            !$this->challengeDbCmd->insertChallenge($challenge, $id, $scriptName)) {
            Logger::error(self::LOG_PREFIX . 'Error while creating a challenge');
            http_response_code(500);
            throw new Exception('ChallengeManager.generateChallenge: Error while generating a challenge');
        }
        return $challenge;
    }

    public function generateToken($id, $scriptName, $data)
    {

        $challenge = $this->generateChallenge($id, $scriptName);

        if (empty($challenge)) {
            Logger::error('Retrieving the challenge was not successful.');
            return;
        }

        $jwk = JWKFactory::createFromKeyFile($this->privKey);
        $algorithmManager = new AlgorithmManager(
            [
                self::getAlgorithm('Signature\\Algorithm', $this->sighAlg)
            ]
        );
        $jwsBuilder = new JWSBuilder($algorithmManager);
        $payload = json_encode([
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 300,
            'challenge' => $challenge,
            'id' => $id,
            'data' => $data
        ]);

        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, ['alg' => $this->sighAlg])
            ->build();

        $serializer = new CompactSerializer();
        $token = $serializer->serialize($jws, 0);

        return $token;
    }

    public function decodeToken($token)
    {
        $algorithmManager = new AlgorithmManager(
            [
                self::getAlgorithm('Signature\\Algorithm', $this->sighAlg)
            ]
        );
        $jwsVerifier = new JWSVerifier($algorithmManager);
        $jwk = JWKFactory::createFromKeyFile($this->pubKey);

        $serializerManager = new JWSSerializerManager([new CompactSerializer()]);
        $jws = $serializerManager->unserialize($token);

        $headerCheckerManager = new HeaderCheckerManager(
            [new AlgorithmChecker([$this->sighAlg])],
            [new JWSTokenSupport()]
        );

        $headerCheckerManager->check($jws, 0);

        $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);

        if (!$isVerified) {
            Logger::error('Perun.updateUes: The token signature is invalid!');
            http_response_code(401);
            exit;
        }

        $claimCheckerManager = new ClaimCheckerManager(
            [
                new Checker\IssuedAtChecker(),
                new Checker\NotBeforeChecker(),
                new Checker\ExpirationTimeChecker(),
            ]
        );
        $claims = json_decode($jws->getPayload(), true);

        $claimCheckerManager->check($claims);

        $challenge = $claims['challenge'];
        $id = $claims['id'];

        $challengeManager = new ChallengeManager();

        $challengeDb = $challengeManager->readChallengeFromDb($id);

        $checkAccessSucceeded = self::checkAccess($challenge, $challengeDb);
        $challengeSuccessfullyDeleted = $challengeManager->deleteChallengeFromDb($id);

        if (!$checkAccessSucceeded || !$challengeSuccessfullyDeleted) {
            exit;
        }

        return $claims;
    }

    private function readChallengeFromDb($id)
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

    private static function checkAccess($challenge, $challengeDb): bool
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

    private function deleteChallengeFromDb($id): bool
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

    private static function getAlgorithm($path, $className)
    {
        $classPath = sprintf('Jose\\Component\\%s\\%s', $path, $className);
        if (! class_exists($classPath)) {
            throw new \Exception('Invalid algorithm specified: ' . $classPath);
        }
        return new $classPath();
    }
}
