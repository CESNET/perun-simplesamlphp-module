<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\SingularAttributeTransformer;

/**
 * Convert locales to language codes in the keys of a map.
 */
class LocalesToLanguages extends SingularAttributeTransformer
{
    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
    }

    /**
     * @override
     */
    public function singleTransform($values)
    {
        $result = [];
        foreach ($values as $locale => $value) {
            $lang = \Locale::getPrimaryLanguage($locale);
            $result[$lang] = array_merge($result[$lang] ?? [], is_array($value) ? $value : [$value]);
        }
        return $result;
    }

    /**
     * @override
     */
    public function singleDescription(string $description)
    {
        return $description;
    }
}
