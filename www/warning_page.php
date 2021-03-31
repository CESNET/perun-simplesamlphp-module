<?php

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Module\perun\Disco;
use SimpleSAML\Module\perun\model\WarningConfiguration;
use SimpleSAML\XHTML\Template;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:warning');

$config = Configuration::getInstance();
$warningInstance = WarningConfiguration::getInstance();
$warningAttributes = $warningInstance->getWarningAttributes();

$t = new Template($config, 'perun:warning-tpl.php');
$t->data[Disco::WARNING_ATTRIBUTES] = $warningAttributes;

$t->show();
