<?php

use \SimpleSAML\Module\perun\AdapterRpc;
use SimpleSAML\Auth\State;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Logger;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:forceAup');
$rpcAdapter = new AdapterRpc();
$rpcConnector = $rpcAdapter->getConnector();
/**
 * @var \SimpleSAML\Module\perun\model\User $user
 */
$user = $state['perun']['user'];

try {
    $userAupsAttr = $rpcConnector->get('attributesManager', 'getAttribute', [
        'user' => $user->getId(),
        'attributeName' => $state['perunUserAupAttr'],
    ]);
    $userAups = $userAupsAttr['value'];
} catch (\Exception $exception) {
    Logger::error('Perun.ForceAup - Error during get userAupsAttr from Perun');
}

foreach ($state['newAups'] as $key => $newAup) {
    if (!($userAups === null) && array_key_exists($key, $userAups)) {
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
