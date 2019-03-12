<?php

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'perun:forceAup');
$rpcAdapter = new sspmod_perun_AdapterRpc();
$rpcConnector = $rpcAdapter->getConnector();
/**
 * @var sspmod_perun_model_User $user
 */
$user = $state['perun']['user'];

try {
    $userAupsAttr = $rpcConnector->get('attributesManager', 'getAttribute', array(
        'user' => $user->getId(),
        'attributeName' => $state['perunUserAupAttr'],
    ));
    $userAups = $userAupsAttr['value'];
} catch (Exception $exception) {
    SimpleSAML\Logger::error('Perun.ForceAup - Error during get userAupsAttr from Perun');
}

foreach ($state['newAups'] as $key => $newAup) {
    if (!($userAups === null) && array_key_exists($key, $userAups)) {
        $userAupList = json_decode($userAups[$key]);
    } else {
        $userAupList = array();
    }

    $newAup->signed_on = date('Y-m-d');
    array_push($userAupList, $newAup);
    $userAups[$key] = json_encode($userAupList);
}

$userAupsAttr['value'] = $userAups;

try {
    $rpcConnector->post('attributesManager', 'setAttribute', array(
        'user' => $user->getId(),
        'attribute' => $userAupsAttr,
    ));

    SimpleSAML\Logger::info('Perun.ForceAup - User accepted usage policy');
} catch (Exception $exception) {
    SimpleSAML\Logger::error('Perun.ForceAup - Error during post data to Perun');
}

SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
