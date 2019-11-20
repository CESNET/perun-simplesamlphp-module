<?php

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Extract list of emails of certain type(s) from contacts.
 */
class EmailList implements AttributeTransformer
{
    private $types;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->types = $config->getArray('types', []);
    }

    /**
     * @override
     */
    public function transform(array $attributes)
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
