<?php

declare(strict_types=1);

use SimpleSAML\Error\Error;
use SimpleSAML\Module\perun\Disco;

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
