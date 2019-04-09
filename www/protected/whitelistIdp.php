<?php

use SimpleSAML\Module\perun\IdpListsService;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Error\Exception;

/**
 * endpoint which whitelist given idp defined by entityID param.
 * Optionally consumes and saves reason param.
 *
 * example call:
 * https://login.example.org/proxy/module.php/perun/protected/whitelistIdp.php?entityId=hey&reason=Attribute%20check%20by%20user
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Michal Prochazka <vyskocipavel@muni.cz>
 */

if (!isset($_REQUEST['entityId'])) {
    sendError("parametr 'entityId' is missing", 400);
}

$entityid = $_REQUEST['entityId'];
$reason = (isset($_REQUEST['reason']) ? $_REQUEST['reason'] : null);

$metadataHandler = MetaDataStorageHandler::getMetadataHandler();
$idpsMatadata = $metadataHandler->getList('saml20-idp-remote');

if (!array_key_exists($entityid, $idpsMatadata)) {
    sendError("unknown IdP with entityId '$entityid'. Metadata not found.", 400);
}

try {
    //FIXME: Not thread safe!!!
    $service = IdpListsService::getInstance();

    if ($service->isWhitelisted($entityid)) {
        if (!$service->isGreylisted($entityid)) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'result' => 'ALREADY_THERE',
                'msg' => "IdP '$entityid' is already whitelisted."
            ));
            exit;
        }
    }

    $service->whitelistIdp($entityid, $reason);

    header('Content-Type: application/json');
    echo json_encode(array(
        'result' => 'ADDED',
        'msg' => "IdP '$entityid' was added to whitelist."
    ));
} catch (Exception $e) {
    sendError($e->getMessage());
}

function sendError($msg, $code = 500)
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array(
        'result' => 'ERROR',
        'msg' => $msg
    ));
    exit;
}
