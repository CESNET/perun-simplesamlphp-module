<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\Module\perun\Auth\Process\PerunAup;
use SimpleSAML\XHTML\Template;

$config = Configuration::getInstance();
$t = new Template($config, PerunAup::TEMPLATE);
$t->data[PerunAup::PARAM_APPROVAL_URL] = $_REQUEST[PerunAup::PARAM_APPROVAL_URL];

$t->show();
