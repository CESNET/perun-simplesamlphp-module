<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\SingularAttributeTransformer;

/**
 * Extract list of emails of certain type(s) from contacts.
 */
class EmailList extends SingularAttributeTransformer
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
    public function singleTransform($values)
    {
        $result = [];
        foreach ($values as $contact) {
            if (
                isset($contact['contactType'])
                && (empty($this->types) || in_array($contact['contactType'], $this->types, true))
                && ! empty($contact['emailAddress'])
            ) {
                $result[] = is_array($contact['emailAddress']) ? $contact['emailAddress'][0] : $contact['emailAddress'];
            }
        }
        return $result;
    }

    /**
     * @override
     */
    public function singleDescription(string $description)
    {
        return sprintf(
            'emails of type%s %s from (%s)',
            count($this->types) > 1 ? 's' : '',
            implode(',', $this->types),
            $description
        );
    }
}
