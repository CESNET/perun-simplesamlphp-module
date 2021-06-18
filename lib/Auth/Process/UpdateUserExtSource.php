<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Algorithm\RS512;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\ChallengeManager;
use SimpleSAML\Module\perun\UpdateUESThread;

/**
 * Class sspmod_perun_Auth_Process_UpdateUserExtSource
 *
 * This filter updates userExtSource attributes when he logs in.
 *
 * @author Dominik Baránek <baranek@ics.muni.cz>
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class UpdateUserExtSource extends ProcessingFilter
{
    private $attrMap;
    private $attrsToConversion;
    private $pathToKey;
    private $signatureAlg;

    const SCRIPT_NAME = 'updateUes';

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert(is_array($config));

        if (!isset($config['attrMap'])) {
            throw new Exception(
                'perun:UpdateUserExtSource: missing mandatory configuration option \'attrMap\'.'
            );
        }

        if (!isset($config['pathToKey'])) {
            throw new Exception(
                'perun:UpdateUserExtSource: missing mandatory configuration option \'pathToKey\'.'
            );
        }

        if (isset($config['arrayToStringConversion'])) {
            $this->attrsToConversion = (array)$config['arrayToStringConversion'];
        } else {
            $this->attrsToConversion = [];
        }

        if (isset($config['signatureAlg'])) {
            $this->signatureAlg = (array)$config['signatureAlg'];
        } else {
            $this->signatureAlg = 'RS512';
        }

        $this->attrMap = (array)$config['attrMap'];
        $this->pathToKey = $config['pathToKey'];
    }

    public function process(&$request)
    {
        $id = uniqid("", true);

        $dataChallenge = [
            'id' => $id,
            'scriptName' => self::SCRIPT_NAME
        ];

        $json = json_encode($dataChallenge);

        $curlChallenge = curl_init();
        curl_setopt($curlChallenge, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curlChallenge, CURLOPT_URL, Module::getModuleURL('perun/getChallenge.php'));
        curl_setopt($curlChallenge, CURLOPT_RETURNTRANSFER, true);

        $challenge = curl_exec($curlChallenge);

        if (empty($challenge)) {
            Logger::error('Retrieving the challenge was not successful.');
            return;
        }

        $jwk = JWKFactory::createFromKeyFile($this->pathToKey);
        $algorithmManager = new AlgorithmManager(
            [
                ChallengeManager::getAlgorithm('Signature\\Algorithm', $this->signatureAlg)
            ]
        );
        $jwsBuilder = new JWSBuilder($algorithmManager);

        $data = [
            'attributes' => $request['Attributes'],
            'attrMap' => $this->attrMap,
            'attrsToConversion' => $this->attrsToConversion,
            'perunUserId' => $request['perun']['user']->getId()
        ];

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
            ->addSignature($jwk, ['alg' => $this->signatureAlg])
            ->build();

        $serializer = new CompactSerializer();
        $token = $serializer->serialize($jws, 0);

        $cmd = 'curl -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($token) . ' ' .
            escapeshellarg(Module::getModuleURL('perun/updateUes.php')) . ' > /dev/null &';

        exec($cmd);
    }
}
