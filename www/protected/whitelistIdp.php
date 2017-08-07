<?php

/**
 * endpoint which whitelist given idp defined by entityID param.
 * Optionally consumes and saves reason param.
 *
 * example call:
 * https://login.example.org/proxy/module.php/perun/protected/whitelistIdp.php?entityId=hey&reason=Attribute%20check%20by%20user
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

if (!isset($_REQUEST['entityId'])) {
	sendError("parametr 'entityId' is missing", 400);
}
$entityid = $_REQUEST['entityId'];
$reason = (isset($_REQUEST['reason'])?$_REQUEST['reason']:null);

$metadataHandler = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpsMatadata = $metadataHandler->getList('saml20-idp-remote');

if (!array_key_exists($entityid, $idpsMatadata)) {
	sendError("unknown IdP with entityId '$entityid'. Metadata not found.", 400);
}


try {
	//FIXME: Not thread safe!!!
	$service = new sspmod_perun_IdpListsServiceCsv();

	if ($service->isWhitelisted($entityid)) {
		if (!$service->isGreylisted($entityid)) {

			// Save new timestamp
			$service->whitelistIdp($entityid, $reason);

			header('Content-Type: application/json');
			echo json_encode(array(
				'result' => 'ALREADY_THERE',
				'msg' => "IdP '$entityid' is already whitelisted."
			));
			exit;

		}
	}

	$service->whitelistIdp($entityid, $reason);

	$whitelist = $service->getLatestWhitelist();
	$greylist = $service->getLatestGreylist();

	header('Content-Type: application/json');
	echo json_encode(array(
		'result' => 'ADDED',
		'whitelist' => $whitelist,
		'greylist' => $greylist
	));

} catch (SimpleSAML_Error_Exception $e) {
	sendError($e->getMessage());
}


function sendError($msg, $code = 500) {
	http_response_code($code);
	header('Content-Type: application/json');
	echo json_encode(array(
		'result' => 'ERROR',
		'msg' => $msg
	));
	exit;
}

?>
