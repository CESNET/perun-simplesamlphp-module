<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\databaseCommand\ChallengesDbCmd;

class ChallengeManager
{
    public const LOG_PREFIX = 'Perun:ChallengeManager: ';

    public const CONFIG_FILE_NAME = 'challenges_config.php';

    public const HASH_ALG = 'hashAlg';

    public const SIG_ALG = 'sigAlg';

    public const CHALLENGE_LENGTH = 'challengeLength';

    public const PUB_KEY = 'pubKey';

    public const PRIV_KEY = 'privKey';

    private $challengeDbCmd;

    private $sigAlg;

    private $hashAlg;

    private $challengeLength;

    private $privKey;

    private $pubKey;

    public function __construct()
    {
        $this->challengeDbCmd = new ChallengesDbCmd();
        $config = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->hashAlg = $config->getString(self::HASH_ALG, 'sha512');
        $this->sigAlg = $config->getString(self::SIG_ALG, 'sha512');
        $this->challengeLength = $config->getInteger(self::CHALLENGE_LENGTH, 32);
        $this->pubKey = $config->getString(self::PUB_KEY);
        $this->privKey = $config->getString(self::PRIV_KEY);
    }

    public function generateChallenge($id, $scriptName): string
    {
        $challenge = hash($this->hashAlg, random_bytes($this->challengeLength));

        if (empty($id) ||
            empty($scriptName) ||
            ! $this->challengeDbCmd->insertChallenge($challenge, $id, $scriptName)) {
            throw new Exception('ChallengeManager.generateChallenge: Error while storing a challenge to DB.');
        }
        return $challenge;
    }

    public function generateToken($id, $scriptName, $data): string
    {
        try {
            $challenge = $this->generateChallenge($id, $scriptName);
        } catch (\Exception $ex) {
            throw new Exception('ChallengeManager.generateToken: Error while generating a challenge');
        }

        $jwk = JWKFactory::createFromKeyFile($this->privKey);
        $algorithmManager = new AlgorithmManager([self::getAlgorithm('Signature\\Algorithm', $this->sigAlg)]);
        $jwsBuilder = new JWSBuilder($algorithmManager);
        $payload = json_encode([
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 300,
            'challenge' => $challenge,
            'id' => $id,
            'scriptName' => $scriptName,
            'data' => $data,
        ]);

        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, [
                'alg' => $this->sigAlg,
            ])
            ->build();

        $serializer = new CompactSerializer();
        $token = $serializer->serialize($jws, 0);

        return $token;
    }

    public function decodeToken($token)
    {
        $algorithmManager = new AlgorithmManager([self::getAlgorithm('Signature\\Algorithm', $this->sigAlg)]);
        $jwsVerifier = new JWSVerifier($algorithmManager);
        $jwk = JWKFactory::createFromKeyFile($this->pubKey);

        $serializerManager = new JWSSerializerManager([new CompactSerializer()]);
        $jws = $serializerManager->unserialize($token);

        $headerCheckerManager = new HeaderCheckerManager(
            [new AlgorithmChecker([$this->sigAlg])],
            [new JWSTokenSupport()]
        );

        $headerCheckerManager->check($jws, 0);

        $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);

        if (! $isVerified) {
            throw new Exception('ChallengeManager.decodeToken: The token signature is invalid!');
        }

        $claimCheckerManager = new ClaimCheckerManager(
            [new IssuedAtChecker(), new NotBeforeChecker(), new ExpirationTimeChecker()]
        );
        $claims = json_decode($jws->getPayload(), true);

        $claimCheckerManager->check($claims);

        $challenge = $claims['challenge'];
        $id = $claims['id'];
        $scriptName = $claims['scriptName'];

        $challengeManager = new self();

        $challengeDb = $challengeManager->readChallengeFromDb($id, $scriptName);

        $checkAccessSucceeded = self::checkAccess($challenge, $challengeDb);
        $challengeSuccessfullyDeleted = $challengeManager->deleteChallengeFromDb($id);

        if (! $checkAccessSucceeded || ! $challengeSuccessfullyDeleted) {
            exit;
        }

        return $claims;
    }

    private function readChallengeFromDb($id, $scriptName)
    {
        if (empty($id) || empty($scriptName)) {
            return null;
        }

        return $this->challengeDbCmd->readChallenge($id, $scriptName);
    }

    private static function checkAccess($challenge, $challengeDb): bool
    {
        if (empty($challenge) || empty($challengeDb)) {
            return false;
        }

        if (! hash_equals($challengeDb, $challenge)) {
            Logger::error(self::LOG_PREFIX . 'Hashes are not equal.');
            return false;
        }

        return true;
    }

    private function deleteChallengeFromDb($id): bool
    {
        if (empty($id)) {
            return false;
        }

        if (! $this->challengeDbCmd->deleteChallenge($id)) {
            Logger::error(self::LOG_PREFIX . 'Error while deleting challenge from the database.');
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
