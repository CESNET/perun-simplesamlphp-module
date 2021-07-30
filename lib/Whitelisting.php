<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

/**
 * Class Whitelisting
 *
 * @package SimpleSAML\Module\perun
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>v
 */
class Whitelisting
{
    public static function getEntityName($metadata)
    {
        if (isset($metadata['UIInfo']['DisplayName'])) {
            $displayName = $metadata['UIInfo']['DisplayName'];
            assert(is_array($displayName)); // Should always be an array of language code -> translation
            if (! empty($displayName)) {
                return preg_replace("/\r|\n/", '', $displayName['en']);
            }
        }
        if (array_key_exists('name', $metadata)) {
            if (is_array($metadata['name'])) {
                return preg_replace("/\r|\n/", '', $metadata['name']['en']);
            }
            return preg_replace("/\r|\n/", '', $metadata['name']);
        }
        return $metadata['entityid'];
    }
}
