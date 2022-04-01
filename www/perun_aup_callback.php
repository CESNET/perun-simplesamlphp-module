<?php

declare(strict_types=1);

use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\perun\Auth\Process\PerunAup;
use SimpleSAML\Module\perun\PerunConstants;

if (empty($_REQUEST[PerunAup::PARAM_STATE_ID])) {
    throw new BadRequest('Missing required \'' . PerunAup::PARAM_STATE_ID . '\' query parameter.');
}
$state = State::loadState($_REQUEST[PerunAup::PARAM_STATE_ID], PerunAup::STAGE);

$filterConfig = $state[PerunConstants::CONTINUE_FILTER_CONFIG];
$perunAup = new PerunAup($filterConfig, null);
$perunAup->process($state);

// we have not been redirected, continue processing
ProcessingChain::resumeProcessing($state);
