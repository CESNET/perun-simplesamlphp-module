<?php

declare(strict_types=1);

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
    protected const STATUS_NAGIOS = 'status_nagios';

    protected const HOST = 'host';

    protected const KEY_PATH = 'key_path';

    protected const LOGIN = 'login';

    protected const COMMAND = 'command';

    private $host;

    private $keyPath;

    private $login;

    private $command;

    public function __construct()
    {
        parent::__construct();

        $config = $this->configuration->getConfigItem(self::STATUS_NAGIOS, null);

        if ($config === null) {
            throw new Exception('Property  \'' . self::STATUS_NAGIOS . '\' is missing or invalid!');
        }

        $this->host = $config->getString(self::HOST, null);
        $this->keyPath = $config->getString(self::KEY_PATH, null);
        $this->login = $config->getString(self::LOGIN, null);
        $this->command = $config->getString(self::COMMAND, null);

        if (empty($this->host)) {
            throw new Exception('Required option \'' . self::HOST . '\' is empty!');
        } elseif (empty($this->keyPath)) {
            throw new Exception('Required option \'' . self::KEY_PATH . '\' is empty!');
        } elseif (empty($this->login)) {
            throw new Exception('Required option \'' . self::LOGIN . '\' is empty!');
        } elseif (empty($this->command)) {
            throw new Exception('Required option \'' . self::COMMAND . '\' is empty!');
        }
    }

    public function getStatus()
    {
        $result = [];

        if (! ($key = file_get_contents($this->keyPath))) {
            throw new Exception('Cannot load ket from path:  \'' . $this->keyPath . '\' !');
        }

        $key = RSA::load($key);
        $ssh = new SSH2($this->host);

        if (! $ssh->login($this->login, $key)) {
            throw new Exception('Error during ssh connection to \'' . $this->login . '@' . $this->host . '\' !');
        }

        $output = $ssh->exec($this->command);
        $lines = explode("\n", $output);
        array_pop($lines);

        foreach ($lines as $line) {
            $lineParts = explode(';', $line);
            $result[$lineParts[0]][$lineParts[1]] = $lineParts[2];
        }

        return $result;
    }
}
