<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Logical and.
 */
class LogicalAnd extends AttributeTransformer
{
    private $output;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->output = $config->getString('output');
    }

    /**
     * @override
     */
    public function transform(array $attributes)
    {
        if (count($attributes) > 0) {
            $output = true;
            foreach ($attributes as $value) {
                $output = $output && $value;
            }
            $attributes[$this->output] = $output;
        }
        return $attributes;
    }

    public function getDescription(array $attributes)
    {
        $attributes[$this->output] = '(' . implode(') and (', $attributes) . ')';
    }
}
