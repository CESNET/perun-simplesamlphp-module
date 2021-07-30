<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\SingularAttributeTransformer;

/**
 * Simplify map for storing in Perun by converting subarrays to strings.
 */
class FlatMap extends SingularAttributeTransformer
{
    private $separator;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->separator = $config->getString('separator', ',');
    }

    /**
     * @override
     */
    public function singleTransform($values)
    {
        return array_map(function ($arr) {
            return implode(',', $arr);
        }, $values);
    }

    /**
     * @override
     */
    public function singleDescription(string $description)
    {
        return sprintf('%s separated (%s)', $this->separator, $description);
    }
}
