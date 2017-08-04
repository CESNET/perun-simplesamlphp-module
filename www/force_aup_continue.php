<?php

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'perun:forceAup');

/**
 * @var sspmod_perun_model_User $user
 */
$user = $state['perun']['user'];



$forceAup = sspmod_perun_RpcConnector::get('attributesManager', 'getAttribute', array(
	'user' => $user->getId(),
	'attributeName' => $state['perunForceAttr'],
));

$aup = sspmod_perun_RpcConnector::get('attributesManager', 'getAttribute', array(
	'user' => $user->getId(),
	'attributeName' => $state['perunAupAttr'],
));



if (empty($aup['value'])) {
	$aup['value'] = array($forceAup['value']);
} else {
	array_push($aup['value'], $forceAup['value']);
}

$forceAup['value'] = null;



sspmod_perun_RpcConnector::post('attributesManager', 'setAttribute', array(
	'user' => $user->getId(),
	'attribute' => $aup,
));

sspmod_perun_RpcConnector::post('attributesManager', 'setAttribute', array(
	'user' => $user->getId(),
	'attribute' => $forceAup,
));



SimpleSAML_Logger::info('Perun.ForceAup - User accepted usage policy');

SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);




