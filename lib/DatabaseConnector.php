<?php

/**
 * Class for getting connection to DB
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class databaseConnector
{
    private $serverName;
    private $port;
    private $username;
    private $password;
    private $databaseName;
    private $whitelistTableName;
    private $greyListTableName;
    private $encryption;
    private $sslCA;
    private $sslCert;
    private $sslKey;
    private $sslCAPath;

    const CONFIG_FILE_NAME = 'module_perun_idpListsServiceDB.php';
    const SERVER = 'serverName';
    const PORT = 'port';
    const USER = 'userName';
    const PASSWORD = 'password';
    const DATABASE = 'databaseName';
    const WHITELIST_TABLE_NAME = 'whiteListTableName';
    const GREYLIST_TABLE_NAME = 'greyListTableName';
    const ENCRYPTION = 'encryption';
    const SSL_CA = 'ssl_ca';
    const SSL_CERT = 'ssl_cert_path';
    const SSL_KEY = 'ssl_key_path';
    const SSL_CA_PATH = 'ssl_ca_path';


    public function __construct()
    {
        $conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->serverName = $conf->getString(self::SERVER);
        $this->port = $conf->getInteger(self::PORT, null);
        $this->username = $conf->getString(self::USER);
        $this->password = $conf->getString(self::PASSWORD);
        $this->databaseName = $conf->getString(self::DATABASE);
        $this->whitelistTableName = $conf->getString(self::WHITELIST_TABLE_NAME);
        $this->greyListTableName = $conf->getString(self::GREYLIST_TABLE_NAME);
        $this->encryption = $conf->getBoolean(self::ENCRYPTION);
        $this->sslCA = $conf->getString(self::SSL_CA);
        $this->sslCert = $conf->getString(self::SSL_CERT);
        $this->sslKey = $conf->getString(self::SSL_KEY);
        $this->sslCAPath = $conf->getString(self::SSL_CA_PATH);
    }

    /**
     * Function returns the connection to db
     * @return mysqli connection
     */
    public function getConnection()
    {
        $conn = mysqli_init();
        if ($this->encryption === true) {
            SimpleSAML\Logger::debug("Getting connection with encryption.");
            mysqli_ssl_set($conn, $this->sslKey, $this->sslCert, $this->sslCA, $this->sslCAPath, null);
            if ($this->port === null) {
                mysqli_real_connect($conn, $this->serverName, $this->username, $this->password, $this->databaseName);
            } else {
                mysqli_real_connect(
                    $conn,
                    $this->serverName,
                    $this->username,
                    $this->password,
                    $this->databaseName,
                    $this->port
                );
            }
        } else {
            if ($this->port === null) {
                mysqli_real_connect($conn, $this->serverName, $this->username, $this->password, $this->databaseName);
            } else {
                mysqli_real_connect(
                    $conn,
                    $this->serverName,
                    $this->username,
                    $this->password,
                    $this->databaseName,
                    $this->port
                );
            }
        }
        return $conn;
    }

    /**
     * Function returns name of table for whitelist
     * @return mixed whitelist table name
     */
    public function getWhiteListTableName()
    {
        return $this->whitelistTableName;
    }

    /**
     * Function returns name of table for greylist
     * @return mixed whitelist table name
     */
    public function getGreyListTableName()
    {
        return $this->greyListTableName;
    }
}
