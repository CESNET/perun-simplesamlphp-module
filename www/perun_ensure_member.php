<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\Module\perun\Auth\Process\PerunEnsureMember;
use SimpleSAML\XHTML\Template;

$config = Configuration::getInstance();
$t = new Template($config, PerunEnsureMember::TEMPLATE);
$t->data[PerunEnsureMember::PARAM_REGISTRATION_URL] = $_REQUEST[PerunEnsureMember::PARAM_REGISTRATION_URL];

$t->show();
