<?php

use SimpleSAML\Auth\State;
use SimpleSAML\Auth\ProcessingChain;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:warningTestSP');

ProcessingChain::resumeProcessing($state);
