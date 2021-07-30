<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Exception as PerunException;

/**
 * Provides interface to call Perun RPC. Note that Perun RPC should be considered as unreliable and authentication
 * process should continue without connection to Perun. e.g. use LDAP instead.
 *
 * Example Usage:
 *
 * try { $attribute = RpcConnector::get('attributesManager', 'getAttribute', [ 'user' => $userId, 'attributeName' =>
 * $attrName, ]); ... } catch (PerunException $pe) { ... }
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class RpcConnector
{
    public const COOKIE_FILE = '/tmp/proxyidp_cookie.txt';

    public const CONNECT_TIMEOUT = 1;

    public const TIMEOUT = 15;

    private $rpcUrl;

    private $user;

    private $password;

    private $serializer;

    /**
     * sspmod_perun_RpcConnector constructor.
     *
     * @param $rpcUrl
     * @param $user
     * @param $password
     * @param $serializer
     */
    public function __construct($rpcUrl, $user, $password, $serializer)
    {
        $this->rpcUrl = $rpcUrl;
        $this->user = $user;
        $this->password = $password;
        $this->serializer = $serializer;
    }

    public function get($manager, $method, $params = [])
    {
        $paramsQuery = http_build_query($params);
        // replace 'paramList[0]=val0' to just 'paramList[]=val0' because perun rpc cannot consume such lists.
        $paramsQuery = preg_replace('/\%5B\d+\%5D/', '%5B%5D', $paramsQuery);

        $ch = curl_init();

        $uri = $this->rpcUrl . $this->serializer . '/' . $manager . '/' . $method;
        curl_setopt($ch, CURLOPT_URL, $uri . '?' . $paramsQuery);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, self::COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, self::COOKIE_FILE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);

        $startTime = microtime(true);
        $json = curl_exec($ch);
        $endTime = microtime(true);
        curl_close($ch);

        $responseTime = round($endTime - $startTime, 3);
        Logger::debug('perun.RPC: GET call ' . $uri . ' with params: ' . $paramsQuery . ', response : ' .
            $json . ' in: ' . $responseTime . 's.');

        $result = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(
                'Cant\'t decode response from Perun. Call: ' . $uri . ', Params: ' . $paramsQuery .
                ', Response: ' . $json
            );
        }
        if (isset($result['errorId'])) {
            self::error($result['errorId'], $result['name'], $result['message'], $uri, $paramsQuery);
        }

        return $result;
    }

    public function post($manager, $method, $params = [])
    {
        $paramsJson = json_encode($params);

        $ch = curl_init();

        $uri = $this->rpcUrl . 'json/' . $manager . '/' . $method;
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->password);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsJson);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            ['Content-Type:application/json', 'Content-Length: ' . strlen($paramsJson)]
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, self::COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, self::COOKIE_FILE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);

        $startTime = microtime(true);
        $json = curl_exec($ch);
        $endTime = microtime(true);
        curl_close($ch);

        $responseTime = round($endTime - $startTime, 3);
        Logger::debug('perun.RPC: POST call ' . $uri . ' with params: ' . $paramsJson . ', response : ' .
            $json . ' in: ' . $responseTime . 's.');

        $result = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(
                'Cant\'t decode response from Perun. Call: ' . $uri . ', Params: ' . $paramsJson .
                ', Response: ' . $json
            );
        }
        if (isset($result['errorId'])) {
            self::error($result['errorId'], $result['name'], $result['message'], $uri, $paramsJson);
        }

        return $result;
    }

    private static function error($id, $name, $message, $uri, $params)
    {
        throw new PerunException(
            $id,
            $name,
            $message . '\ncall: ' . $uri . ', params: ' . var_export($params, true)
        );
    }
}
