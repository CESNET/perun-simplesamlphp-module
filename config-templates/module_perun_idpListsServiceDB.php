<?php

declare(strict_types=1);

/**
 * This is example configuration for connection to database with whitelist/greylist Copy this file to default config
 * directory and edit the properties.
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
$config = [
    /*
     * Fill the serverName
     */
    'serverName' => 'localhost',

    /*
     * If you want to use the default port, please comment this option
     */
    'port' => 3306,

    /*
     * Fill the user name
     */
    'userName' => 'proxy',

    /*
     * Fill the password
     */
    'password' => 'passwd',

    /*
     * Fill the database name
     */
    'databaseName' => 'Proxy',

    /*
     * Fill the table name for whiteList
     */
    'whiteListTableName' => 'whiteList',

    /*
     * Fill the table name for greyList
     */
    'greyListTableName' => 'greyList',

    /*
     * Fill true, if your SQL Server used encrypted connections. False if not
     */
    'encryption' => true / false,

    /*
     * The path name to the certificate authority file.
     *
     * If your SQL Server used encrypted connections, you must fill this option.
     */
    'ssl_ca' => '/example/ca.pem',

    /*
     * The path name to the certificate file.
     *
     * If your SQL Server used encrypted connections, you must fill this option.
     */
    'ssl_cert_path' => '/example/cert.pem',

    /*
     * The path name to the key file.
     *
     * If your SQL Server used encrypted connections, you must fill this option.
     */
    'ssl_key_path' => '/example/key.pem',

    /*
     * The pathname to a directory that contains trusted SSL CA certificates in PEM format.
     *
     * If your SQL Server used encrypted connections, you must fill this option.
     */
    'ssl_ca_path' => '/etc/ssl',
];
