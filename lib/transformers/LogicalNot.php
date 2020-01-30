<?php

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
    private $mapping;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->mapping = $config->getArray('mapping');
    }

    /**
     * @override
     */
    public function singleTransform(array $values)
    {
        $result = [];
        foreach ($values as $from => $value) {
            $to = $this->mapping[$from];
            $result[$to] = !$value;
        }
        return $result;
    }

    /**
     * @override
     */
    public function singleDescription(string $description)
    {
        return sprintf('not (%s)', $description);
    }
}
