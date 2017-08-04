<?php

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'perun:forceAup');

$config = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($config, 'perun:force-aup-tpl.php');
$t->data['aupUrl'] = $state['aupUrl'];
$t->data['aupVersion'] = $state['aupVersion'];
$t->show();





