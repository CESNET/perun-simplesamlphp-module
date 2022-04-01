<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

$config = Configuration::getInstance();

$t = new Template($config, 'perun:consolidator-tpl.php');
$t->show();
