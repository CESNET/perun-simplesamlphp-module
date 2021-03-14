<?php

namespace SimpleSAML\Module\perun;

use phpseclib3\Crypt\RSA;
use phpseclib3\Net\SSH2;
use SimpleSAML\Error\Exception;


/**
 * Class sspmod_perun_NagiosStatusConnector
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class NagiosStatusConnector extends StatusConnector
{
    const STATUS_NAGIOS = 'status_nagios';
    const HOST = 'host';
    const KEY_PATH = 'key_path';
    const LOGIN = 'login';
    const COMMAND = 'command';

    private $params;

    private $host;
    private $keyPath;
    private $login;
    private $command;

    /**
     * NagiosStatusConnector constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->params = $this->configuration->getArray(self::STATUS_NAGIOS, []);

        if (empty($this->params[self::HOST])) {
            throw new Exception('Required option \'' . self::HOST . '\' is empty!');
        } elseif (empty($this->params[self::KEY_PATH])) {
            throw new Exception('Required option \'' . self::KEY_PATH . '\' is empty!');
        } elseif (empty($this->params[self::LOGIN])) {
            throw new Exception('Required option \'' . self::LOGIN . '\' is empty!');
        } elseif (empty($this->params[self::COMMAND])) {
            throw new Exception('Required option \'' . self::COMMAND . '\' is empty!');
        }

        $this->host = $this->params[self::HOST];
        $this->keyPath = $this->params[self::KEY_PATH];
        $this->login = $this->params[self::LOGIN];
        $this->command = $this->params[self::COMMAND];
    }


    public function getStatus()
    {
        $result = [];

        $key = RSA::load(file_get_contents($this->keyPath));
        $ssh = new SSH2($this->host);

        if (!$ssh->login($this->login, $key)) {
            throw new Exception('Error durigng ssh connection to \'' . $this->login . '@' . $this->host . '\' !');
        }

        $output = $ssh->exec($this->command);
        $lines = explode("\n", $output);
        array_pop($lines);

        foreach ($lines as $line) {
            $lineParts = explode(";", $line);
            $result[$lineParts[0]][$lineParts[1]] = $lineParts[2];
        }

        return $result;
    }
}
