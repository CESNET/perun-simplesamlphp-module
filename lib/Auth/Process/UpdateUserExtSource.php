<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Error\Exception;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\ChallengeManager;

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
    public const SCRIPT_NAME = 'updateUes';

    private $attrMap;

    private $attrsToConversion;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert(is_array($config));

        if (! isset($config['attrMap'])) {
            throw new Exception('perun:UpdateUserExtSource: missing mandatory configuration option \'attrMap\'.');
        }

        if (isset($config['arrayToStringConversion'])) {
            $this->attrsToConversion = (array) $config['arrayToStringConversion'];
        } else {
            $this->attrsToConversion = [];
        }

        $this->attrMap = (array) $config['attrMap'];
    }

    public function process(&$request)
    {
        $id = uniqid('', true);

        $challengeManager = new ChallengeManager();
        $data = [
            'attributes' => $request['Attributes'],
            'attrMap' => $this->attrMap,
            'attrsToConversion' => $this->attrsToConversion,
            'perunUserId' => $request['perun']['user']->getId(),
        ];
        $token = $challengeManager->generateToken($id, self::SCRIPT_NAME, $data);

        $cmd = 'curl -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($token) . ' ' .
            escapeshellarg(Module::getModuleURL('perun/updateUes.php')) . ' > /dev/null &';

        exec($cmd);
    }
}
