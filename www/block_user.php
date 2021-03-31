<?php

use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

$config = Configuration::getInstance();

$t = new Template($config, 'perun:block-user-tpl.php');
$t->show();
