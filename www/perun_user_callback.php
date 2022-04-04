<?php

declare(strict_types=1);

use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\perun\Auth\Process\PerunUser;
use SimpleSAML\Module\perun\PerunConstants;

if (empty($_REQUEST[PerunUser::PARAM_STATE_ID])) {
    throw new BadRequest('Missing required \'' . PerunUser::PARAM_STATE_ID . '\' query parameter.');
}
$state = State::loadState($_REQUEST[PerunUser::PARAM_STATE_ID], PerunUser::STAGE);

$filterConfig = $state[PerunConstants::CONTINUE_FILTER_CONFIG];
$perunUser = new PerunUser($filterConfig, null);
$perunUser->process($state);

// we have not been redirected, continue processing
ProcessingChain::resumeProcessing($state);
