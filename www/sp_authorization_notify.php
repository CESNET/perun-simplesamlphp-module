<?php

declare(strict_types=1);

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\perun\Auth\Process\SpAuthorization;
use SimpleSAML\Module\perun\PerunConstants;
use SimpleSAML\XHTML\Template;

if (empty($_REQUEST[SpAuthorization::PARAM_STATE_ID])) {
    throw new BadRequest('Missing required \'' . SpAuthorization::PARAM_STATE_ID . '\' query parameter.');
}
$state = State::loadState($_REQUEST[SpAuthorization::PARAM_STATE_ID], SpAuthorization::STAGE);

$config = Configuration::getInstance();
$t = new Template($config, SpAuthorization::TEMPLATE_NOTIFY);

$t->data[SpAuthorization::PARAM_SP_METADATA] = $state[PerunConstants::SP_METADATA];
$t->data[SpAuthorization::PARAM_REGISTRATION_URL] = $_REQUEST[SpAuthorization::PARAM_REGISTRATION_URL];

$t->show();
