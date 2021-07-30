<?php

declare(strict_types=1);

use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\AdapterRpc;
use SimpleSAML\Module\perun\AttributeUtils;
use SimpleSAML\Module\perun\model\User;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:forceAup');
$rpcAdapter = new AdapterRpc();
$rpcConnector = $rpcAdapter->getConnector();
/** @var User $user */
$user = $state['perun']['user'];

try {
    $attrName = AttributeUtils::getRpcAttrName($state['perunUserAupAttr']);
    $userAupsAttr = $rpcConnector->get('attributesManager', 'getAttribute', [
        'user' => $user->getId(),
        'attributeName' => $attrName,
    ]);
    $userAups = $userAupsAttr['value'];
} catch (\Exception $exception) {
    Logger::error('Perun.ForceAup - Error during get userAupsAttr from Perun');
}

foreach ($state['newAups'] as $key => $newAup) {
    if (! ($userAups === null) && array_key_exists($key, $userAups)) {
        $userAupList = json_decode($userAups[$key]);
    } else {
        $userAupList = [];
    }

    $newAup->signed_on = date('Y-m-d');
    array_push($userAupList, $newAup);
    $userAups[$key] = json_encode($userAupList);
}

$userAupsAttr['value'] = $userAups;

try {
    $rpcConnector->post('attributesManager', 'setAttribute', [
        'user' => $user->getId(),
        'attribute' => $userAupsAttr,
    ]);

    Logger::info('Perun.ForceAup - User accepted usage policy');
} catch (\Exception $exception) {
    Logger::error('Perun.ForceAup - Error during post data to Perun');
}

ProcessingChain::resumeProcessing($state);
