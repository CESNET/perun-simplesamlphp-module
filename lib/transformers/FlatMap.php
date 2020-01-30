<?php

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
    public function singleTransform(array $values)
    {
        return array_map([$this, 'arrayToString'], $values);
    }

    /**
     * @param array $arr
     * @return string
     */
    private function arrayToString(array $arr) {
        return implode($this->separator, $arr);
    }

    /**
     * @override
     */
    public function singleDescription(string $description)
    {
        return sprintf('%s separated (%s)', $this->separator, $description);
    }
}
