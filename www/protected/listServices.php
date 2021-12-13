<?php

declare(strict_types=1);

use SimpleSAML\Metadata\MetaDataStorageHandler;

/**
 * List all services from the saml20-sp-remote metadata which have enabled consent. Internal services do not have
 * consent enabled.
 *
 * Returns list of service in format: Name|Description|Organization name|Privacy policy URL|Code of conduct|Information
 * URL\n
 *
 * Author: Michal Prochazka <michalp@ics.muni.cz> Author: Ondrej Velisek <ondrejvelisek@gmail.com>
 *
 * TODO: Use standardized format (JSON)
 */
$metadataHandler = MetaDataStorageHandler::getMetadataHandler();
$spsMetadata = $metadataHandler->getList('saml20-sp-remote');

header('Content-Type: text/plain');

$delimiter = '|';

foreach ($spsMetadata as $entityID => $spMetadata) {
    if (array_key_exists('showOnServicesList', $spMetadata) && true === $spMetadata['showOnServicesList']) {
        if (array_key_exists('name', $spMetadata)) {
            echo $spMetadata['name']['en'];
        }
        echo $delimiter;

        if (array_key_exists('description', $spMetadata)) {
            echo $spMetadata['description']['en'];
        }
        echo $delimiter;

        if (array_key_exists('OrganizationName', $spMetadata)) {
            echo $spMetadata['OrganizationName']['en'];
        }
        echo $delimiter;

        if (array_key_exists('privacypolicy', $spMetadata)) {
            echo $spMetadata['privacypolicy'];
        }
        echo $delimiter;

        if (array_key_exists('CoCo', $spMetadata) && true === $spMetadata['CoCo']) {
            echo 'yes';
        } else {
            echo 'no';
        }
        echo $delimiter;

        if (array_key_exists('InformationURL', $spMetadata)) {
            echo $spMetadata['InformationURL']['en'];
        }

        echo "\n";
    }
}
