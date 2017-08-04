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


$vo = $adapter->getVoByShortName($_REQUEST[sspmod_perun_Auth_Process_PerunIdentity::VO_SHORTNAME]);


$groups = array();
foreach ($_REQUEST['groupNames'] as $groupName) {
	array_push($groups, $adapter->getGroupByName($vo, $groupName));
}


$config = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($config, 'perun:choose-group-tpl.php');
$t->data['registerUrl'] = $_REQUEST[sspmod_perun_Auth_Process_PerunIdentity::REGISTER_URL];
$t->data['callbackParamName'] = $_REQUEST[sspmod_perun_Auth_Process_PerunIdentity::CALLBACK_PARAM_NAME];
$t->data['callbackUrl'] = $_REQUEST['callbackUrl'];
$t->data['vo'] = $vo;
$t->data['groups'] = $groups;
$t->show();