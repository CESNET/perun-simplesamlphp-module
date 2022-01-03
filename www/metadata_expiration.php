<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;

/**
 * This script loads all the metadata and finds the one which is closest to expiration. Then it sends the time to
 * expiration to the template.
 *
 * This can be used to check whether the meta refresh works without problems.
 */
$config = Configuration::getInstance();
$session = Session::getSessionFromRequest();

$metadata = MetaDataStorageHandler::getMetadataHandler();

$metaentries = [
    'hosted' => [],
    'remote' => [],
];
$metaentries['remote']['saml20-idp-remote'] = $metadata->getList('saml20-idp-remote');
$metaentries['remote']['shib13-idp-remote'] = $metadata->getList('shib13-idp-remote');

if (true === $config->getBoolean('enable.saml20-idp', false)) {
    try {
        $metaentries['remote']['saml20-sp-remote'] = $metadata->getList('saml20-sp-remote');
    } catch (Exception $e) {
        SimpleSAML\Logger::error('Federation: Error loading saml20-idp: ' . $e->getMessage());
    }
}

if (true === $config->getBoolean('enable.shib13-idp', false)) {
    try {
        $metaentries['remote']['shib13-sp-remote'] = $metadata->getList('shib13-sp-remote');
    } catch (Exception $e) {
        SimpleSAML\Logger::error('Federation: Error loading shib13-idp: ' . $e->getMessage());
    }
}

if (true === $config->getBoolean('enable.adfs-idp', false)) {
    try {
        $metaentries['remote']['adfs-sp-remote'] = $metadata->getList('adfs-sp-remote');
    } catch (Exception $e) {
        SimpleSAML\Logger::error('Federation: Error loading adfs-idp: ' . $e->getMessage());
    }
}

foreach ($metaentries['remote'] as $key => $value) {
    if (empty($value)) {
        unset($metaentries['remote'][$key]);
    }
}

$now = time();
$closestExpiration = null;

foreach ($metaentries['remote'] as $setkey => $set) {
    foreach ($set as $entry) {
        if (array_key_exists('expire', $entry)) {
            $expires = number_format(($entry['expire'] - $now) / 3600, 1);
            null === $closestExpiration ?
                $closestExpiration = $expires : $closestExpiration = min($closestExpiration, $expires);
        }
    }
}

$t = new Template($config, 'perun:metadata_expiration-tpl.php');
$t->data['closestExpiration'] = $closestExpiration;
$t->show();
