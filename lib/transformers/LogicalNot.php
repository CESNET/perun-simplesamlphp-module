<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\SingularAttributeTransformer;

/**
 * Logical negation.
 */
class LogicalNot extends SingularAttributeTransformer
{
    /**
     * @override
     */
    public function singleTransform($value)
    {
        return ! $value;
    }

    /**
     * @override
     */
    public function singleDescription(string $description)
    {
        return sprintf('not (%s)', $description);
    }
}
