<?php
/**
 * This page let user select one group and redirect him to a url where he can register to group.
 *
 * It prepares model data for Template.
 *
 * See sspmod_perun_Auth_Process_PerunIdentity for mor information.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

$adapter = sspmod_perun_Adapter::getInstance($_REQUEST[sspmod_perun_Auth_Process_PerunIdentity::INTERFACE_PROPNAME]);
$rpcAdapter = new sspmod_perun_AdapterRpc();
$spEntityId = $_REQUEST['spEntityId'];
$vosIdForRegistration = $_REQUEST['vosIdForRegistration'];
$stateId = $_REQUEST['stateId'];
$spGroups = $adapter->getSpGroups($spEntityId);
$registerUrlBase = $_REQUEST[sspmod_perun_Auth_Process_PerunIdentity::REGISTER_URL_BASE];
$vosForRegistration = array();
$groupsForRegistration = array();

foreach ($spGroups as $group) {
	if (in_array($group->getVoId(), $vosIdForRegistration)) {
		if ($group->getName() == "members" || $rpcAdapter->hasRegistrationForm($group)) {
			$vo = $adapter->getVoById($group->getVoId());
			if (!isset($vosForRegistration[$vo->getShortName()])) {
				$vosForRegistration[$vo->getShortName()] = $vo;
			}
			array_push($groupsForRegistration, $group);
		}
	}
}

if (empty($groupsForRegistration)) {
	sspmod_perun_Auth_Process_PerunIdentity::unauthorized($_REQUEST);

} elseif (count($groupsForRegistration) == 1) {
	$params = array();
	$vo = explode(':', $groupsForRegistration[0]->getUniqueName(),2)[0];
	$group = $groupsForRegistration[0]->getName();
	$callback = SimpleSAML\Module::getModuleURL('perun/perun_identity_callback.php', array('stateId' => $stateId));

	$params['vo'] = $vo;

	if ($group !== "members") {
		$params['group'] = $group;
	}

	$params[sspmod_perun_Auth_Process_PerunIdentity::TARGET_NEW] = $callback;
	$params[sspmod_perun_Auth_Process_PerunIdentity::TARGET_EXISTING] = $callback;
	$params[sspmod_perun_Auth_Process_PerunIdentity::TARGET_EXTENDED] = $callback;

	$url = SimpleSAML\Module::getModuleURL('perun/unauthorized_access_go_to_registration.php');
	\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $stateId, 'SPMetadata' => $_REQUEST['SPMetadata'], 'registerUrL' => $registerUrlBase , 'params' => $params));
}

$config = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($config, 'perun:choose-vo-and-group-tpl.php');
$t->data['registerUrlBase'] = $registerUrlBase;
$t->data['callbackUrl'] = $_REQUEST['callbackUrl'];
$t->data['vos'] = $vosForRegistration;
$t->data['groups'] = $groupsForRegistration;
$t->data['SPMetadata'] = $_REQUEST['SPMetadata'];
$t->data['stateId'] = $_REQUEST['stateId'];
$t->show();
