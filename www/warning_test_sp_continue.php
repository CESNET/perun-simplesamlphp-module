<?php

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'perun:warningTestSP');

SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
