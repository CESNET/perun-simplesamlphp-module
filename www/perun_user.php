<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\Module\perun\Auth\Process\PerunUser;
use SimpleSAML\XHTML\Template;

$config = Configuration::getInstance();
$t = new Template($config, PerunUser::TEMPLATE);
$t->data[PerunUser::PARAM_REGISTRATION_URL] = $_REQUEST[PerunUser::PARAM_REGISTRATION_URL];

$t->show();
