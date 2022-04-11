<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Configuration;
use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Get contacts array from lists in contact attributes.
 */
class ContactsToArray extends AttributeTransformer
{
    private $contactTypes;

    private $removeSource;

    private $outputAttr;

    /**
     * @override
     */
    public function __construct(Configuration $config)
    {
        $this->contactTypes = $config->getArray('contactTypes');
        $this->removeSource = !$config->getBoolean('keepSource', true);
        $this->outputAttr = $config->getString('outputAttr', 'contacts');
    }

    /**
     * @override
     */
    public function transform(array $attributes)
    {
        $result = [];
        $contacts = [];
        foreach ($attributes as $attribute => $emailList) {
            if ($this->removeSource) {
                $result[$attribute] = null;
            }
            if (!empty($emailList) && isset($this->contactTypes[$attribute])) {
                foreach ($emailList as $email) {
                    $contacts[] = [
                        'contactType' => $this->contactTypes[$attribute],
                        'emailAddress' => $email,
                    ];
                }
            }
        }
        // contacts array
        return array_merge($result, [
            $this->outputAttr => $contacts,
        ]);
    }
}
