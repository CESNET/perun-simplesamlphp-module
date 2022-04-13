<?php

declare(strict_types=1);

use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\perun\Auth\Process\PerunEnsureMember;
use SimpleSAML\Module\perun\PerunConstants;

if (empty($_REQUEST[PerunEnsureMember::PARAM_STATE_ID])) {
    throw new BadRequest('Missing required \'' . PerunEnsureMember::PARAM_STATE_ID . '\' query parameter.');
}

$state = State::loadState($_REQUEST[PerunEnsureMember::PARAM_STATE_ID], PerunEnsureMember::STAGE);

$filterConfig = $state[PerunConstants::CONTINUE_FILTER_CONFIG];

$perunEnsureMember = new PerunEnsureMember($filterConfig, null);
$perunEnsureMember->process($state);

// we have not been redirected, continue processing
ProcessingChain::resumeProcessing($state);
