<?php

declare(strict_types=1);

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:forceAup');

$config = Configuration::getInstance();

$t = new Template($config, 'perun:force-aup-tpl.php');
$t->data['newAups'] = $state['newAups'];
$t->show();
