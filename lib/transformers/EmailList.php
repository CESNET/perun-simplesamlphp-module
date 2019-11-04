<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

class EmailList implements AttributeTransformer
{
    public function __construct()
    {
    }

    public function transform($attributes, $config)
    {
        $types = $config['types'] ?? [];
        $result = [];
        foreach ($attributes as $attribute => $value) {
            $result[$attribute] = self::getEmailsByType($value, $types);
        }
        return $result;
    }

    private static function getEmailsByType(array $contacts, array $types = [])
    {
        $result = [];
        foreach ($contacts as $contact) {
            if (
                isset($contact['contactType'])
                && (empty($types) || in_array($contact['contactType'], $types, true))
                && !empty($contact['emailAddress'])
            ) {
                $result[] = $contact['emailAddress'];
            }
        }
        return $result;
    }
}
