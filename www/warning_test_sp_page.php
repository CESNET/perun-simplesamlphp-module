<?php

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'perun:warningTestSP');

$config = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($config, 'perun:warning-test-sp-tpl.php');
$t->show();

