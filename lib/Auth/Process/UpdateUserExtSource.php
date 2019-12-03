<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\perun\UpdateUESThread;

/**
 * Class sspmod_perun_Auth_Process_UpdateUserExtSource
 *
 * This filter updates userExtSource attributes when he logs in.
 *
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class UpdateUserExtSource extends ProcessingFilter
{
    private $attrMap;
    private $attrsToConversion;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert(is_array($config));

        if (!isset($config['attrMap'])) {
            throw new Exception(
                'perun:UpdateUserExtSource: missing mandatory configuration option \'attrMap\'.'
            );
        }

        if (isset($config['arrayToStringConversion'])) {
            $this->attrsToConversion = (array)$config['arrayToStringConversion'];
        } else {
            $this->attrsToConversion = [];
        }

        $this->attrMap = (array)$config['attrMap'];
    }

    public function process(&$request)
    {
        $data = [
            'attributes' => $request['Attributes'],
            'attrMap' => $this->attrMap,
            'attrsToConversion' => $this->attrsToConversion,
            'perunUserId' => $request['perun']['user']->getId()
        ];

        $cmd = 'curl -X POST -H "Content-Type: application/json" -d \'' . json_encode($data) . '\' ' .
               Module::getModuleURL('perun/updateUes.php') . ' > /dev/null &';
        exec($cmd);
    }
}
