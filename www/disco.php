<?php

use \SimpleSAML\Module\perun\Disco;
use SimpleSAML\Error\Error;

try {
    $discoHandler = new Disco(['saml20-idp-remote'], 'poweridpdisco');
} catch (\Exception $exception) {
    // An error here should be caused by invalid query parameters
    throw new Error('DISCOPARAMS', $exception);
}

try {
    $discoHandler->handleRequest();
} catch (\Exception $exception) {
    // An error here should be caused by metadata
    throw new Error('METADATA', $exception);
}
