<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun;

/**
 * An abstract class for attribute transformers who apply the same function to all supplied attributes individually.
 */
abstract class SingularAttributeTransformer
{
    /**
     * @override
     */
    public function transform(array $attributes)
    {
        return array_map([$this, 'singleTransform'], $attributes);
    }

    /**
     * Transform one attribute. The output is saved into the source attribute.
     *
     * @param $value
     */
    abstract public function singleTransform($value);

    /**
     * @override
     */
    public function getDescription(array $attributes)
    {
        return array_map([$this, 'singleDescription'], $attributes);
    }

    /**
     * Get human readable description of the transformation performed on one attribute.
     *
     * @param string $description current description
     */
    abstract public function singleDescription(string $description);
}
