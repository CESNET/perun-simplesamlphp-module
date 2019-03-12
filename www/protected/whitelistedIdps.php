<?php

/**
 * List all whitelisted IdPs.
 *
 * Returns list of service in format:
 * IdP Name^IdP EnityID\n
 *
 * Author: Michal Prochazka <michalp@ics.muni.cz>
 * Author: Ondrej Velisek <ondrejvelisek@gmail.com>
 * Author: Pavel Vyskocil <vyskocilpavel@muni.cz>
 *
 * TODO: Use standardized format (JSON)
 */

$metadataHandler = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpsMetadata = $metadataHandler->getList('saml20-idp-remote');

$service = sspmod_perun_IdpListsService::getInstance();

header('Content-Type: text/plain');

$delimiter = '^';

$idps = $service->getWhitelist();

foreach ($idps as $idp) {
    $entityID = $idp['entityid'];

    print getEntityName($idpsMetadata[$entityID]);

    print $delimiter;

    print $entityID;

    print "\n";
}


function getEntityName($metadata)
{
    if (isset($metadata['UIInfo']['DisplayName'])) {
        $displayName = $metadata['UIInfo']['DisplayName'];
        assert('is_array($displayName)'); // Should always be an array of language code -> translation
        if (!empty($displayName)) {
            return preg_replace("/\r|\n/", "", $displayName['en']);
        }
    }
    if (array_key_exists('name', $metadata)) {
        if (is_array($metadata['name'])) {
            return preg_replace("/\r|\n/", "", $metadata['name']['en']);
        } else {
            return preg_replace("/\r|\n/", "", $metadata['name']);
        }
    }
    return $metadata['entityid'];
}
