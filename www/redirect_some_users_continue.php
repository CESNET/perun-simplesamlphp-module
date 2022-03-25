<?php

use SimpleSAML\Auth\State;
use SimpleSAML\Auth\ProcessingChain;

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'perun:redirectSomeUsers');

ProcessingChain::resumeProcessing($state);
