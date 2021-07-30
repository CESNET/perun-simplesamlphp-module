<?php

declare(strict_types=1);

use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\perun\IdpListsService;
use SimpleSAML\Module\perun\Whitelisting;

/**
 * List all whitelisted IdPs.
 *
 * Returns list of service in format: IdP Name^IdP EnityID\n
 *
 * Author: Michal Prochazka <michalp@ics.muni.cz> Author: Ondrej Velisek <ondrejvelisek@gmail.com> Author: Pavel
 * Vyskocil <vyskocilpavel@muni.cz>
 *
 * TODO: Use standardized format (JSON)
 */

$metadataHandler = MetaDataStorageHandler::getMetadataHandler();
$idpsMetadata = $metadataHandler->getList('saml20-idp-remote');

$service = IdpListsService::getInstance();

header('Content-Type: text/plain');

$delimiter = '^';

$idps = $service->getWhitelist();

foreach ($idps as $idp) {
    $entityID = $idp['entityid'];

    print Whitelisting::getEntityName($idpsMetadata[$entityID]);

    print $delimiter;

    print $entityID;

    print "\n";
}
