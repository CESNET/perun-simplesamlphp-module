<?php

/**
 * Provides interface to call Perun RPC.
 * Configuration file 'module_perun.php' should be placed in default config folder of SimpleSAMLphp.
 * Example of file is in config-template folder.
 * Note that Perun RPC should be considered as unreliable
 * and authentication process should continue without connection to Perun. e.g. use LDAP instead.
 *
 * Example Usage:
 *
 * try {
 *		$attribute = sspmod_perun_RpcConnector::get('attributesManager', 'getAttribute', array(
 *			'user' => $userId,
 *			'attributeName' => $attrName,
 * 		));
 * 		...
 * } catch (Perun_Exception $pe) {
 *		...
 * }
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_RpcConnector
{
	const CONFIG_FILE_NAME = 'module_perun.php';
	const PROPNAME_URL  = 'rpc.url';
	const PROPNAME_USER = 'rpc.username';
	const PROPNAME_PASS = 'rpc.password';


	public static function get($manager, $method, $params = array()) {
		$paramsQuery = http_build_query($params);
		// replace 'paramList[0]=val0' to just 'paramList[]=val0' because perun rpc cannot consume such lists.
		$paramsQuery = preg_replace('/\%5B\d+\%5D/', '%5B%5D', $paramsQuery);

		$conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		$rpc_url = $conf->getString(self::PROPNAME_URL);
		$user = $conf->getString(self::PROPNAME_USER);
		$pass = $conf->getString(self::PROPNAME_PASS);

		$ch = curl_init();

		$uri = $rpc_url .'json/'.  $manager .'/'. $method;
		curl_setopt($ch, CURLOPT_URL, $uri .'?'. $paramsQuery);
		curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$json = curl_exec($ch);
		curl_close($ch);

		SimpleSAML\Logger::debug("perun.RPC: GET call $uri with params: " . $paramsQuery . ", response: " . $json);

		$result = json_decode($json, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new SimpleSAML_Error_Exception("Cant't decode response from Perun. Call: $uri, Params: $paramsQuery, Response: $json");
		}
		if (isset($result['errorId'])) {
			self::error($result['errorId'], $result['name'], $result['message'], $uri, $paramsQuery);
		}

		return $result;
	}


	public static function post($manager, $method, $params = array()) {
		$paramsJson = json_encode($params);

		$conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		$rpc_url = $conf->getString(self::PROPNAME_URL);
		$user = $conf->getString(self::PROPNAME_USER);
		$pass = $conf->getString(self::PROPNAME_PASS);

		$ch = curl_init();

		$uri = $rpc_url .'json/'.  $manager .'/'. $method;
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsJson);
		curl_setopt($ch, CURLOPT_HTTPHEADER,
			array('Content-Type:application/json',
				'Content-Length: ' . strlen($paramsJson))
		);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$json = curl_exec($ch);
		curl_close($ch);

		SimpleSAML\Logger::debug("perun.RPC: POST call $uri with params: " . $paramsJson . ", response: " . $json);

		$result = json_decode($json, true);



		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new SimpleSAML_Error_Exception("Cant't decode response from Perun. Call: $uri, Params: $paramsJson, Response: $json");
		}
		if (isset($result['errorId'])) {
			self::error($result['errorId'], $result['name'], $result['message'], $uri, $paramsJson);
		}

		return $result;
	}


	private static function error($id, $name, $message, $uri, $params) {
		throw new sspmod_perun_Exception($id, $name, $message . "\ncall: $uri, params: " . var_export($params, true));
	}

}


