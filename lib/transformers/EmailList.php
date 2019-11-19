<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

class EmailList implements AttributeTransformer
{
    private $types;

    public function __construct($config)
    {
        $this->types = $config['types'] ?? [];
    }

    public function transform($attributes)
    {
        $result = [];
        foreach ($attributes as $attribute => $value) {
            $result[$attribute] = $this->getEmailsByType($value);
        }
        return $result;
    }

    private function getEmailsByType(array $contacts)
    {
        $result = [];
        foreach ($contacts as $contact) {
            if (
                isset($contact['contactType'])
                && (empty($this->types) || in_array($contact['contactType'], $this->types, true))
                && !empty($contact['emailAddress'])
            ) {
                $result[] = is_array($contact['emailAddress']) ? $contact['emailAddress'][0] : $contact['emailAddress'];
            }
        }
        return $result;
    }
}
