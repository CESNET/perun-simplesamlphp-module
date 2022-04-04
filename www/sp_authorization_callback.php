<?php

declare(strict_types=1);

use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\perun\Auth\Process\PerunUser;
use SimpleSAML\Module\perun\Auth\Process\SpAuthorization;
use SimpleSAML\Module\perun\PerunConstants;

if (empty($_REQUEST[PerunUser::PARAM_STATE_ID])) {
    throw new BadRequest('Missing required \'' . PerunUser::PARAM_STATE_ID . '\' query parameter.');
}
$state = State::loadState($_REQUEST[PerunUser::PARAM_STATE_ID], PerunUser::STAGE);

$filterConfig = $state[PerunConstants::CONTINUE_FILTER_CONFIG];
$spAuthorization = new SpAuthorization($filterConfig, null);
$spAuthorization->process($state);

// we have not been redirected, continue processing
ProcessingChain::resumeProcessing($state);
