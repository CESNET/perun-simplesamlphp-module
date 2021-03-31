<?php

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:warning');

$config = Configuration::getInstance();

$t = new Template($config, 'perun:warning-test-sp-tpl.php');
$t->show();
