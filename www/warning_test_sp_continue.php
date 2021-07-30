<?php

declare(strict_types=1);

use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:warningTestSP');

ProcessingChain::resumeProcessing($state);
