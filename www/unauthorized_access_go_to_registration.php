<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

$id = $_REQUEST['StateId'];

$config = Configuration::getInstance();
$t = new Template($config, 'perun:unauthorized-access-go-to-registration-tpl.php');
$t->data['SPMetadata'] = $_REQUEST['SPMetadata'];
$t->data['registerUrL'] = $_REQUEST['registerUrL'];
$t->data['params'] = $_REQUEST['params'];

$t->show();
