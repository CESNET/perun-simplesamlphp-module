<?php

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Performs replacements. Based on SSP built in core:AttributeAlter authentication processing filter.
 * @see https://simplesamlphp.org/docs/stable/core:authproc_attributealter
 */
class AttributeAlter implements AttributeTransformer
{
    const ATTRIBUTES_KEY = 'Attributes';

    private $config;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->config = $config->toArray();
    }

    /**
     * @override
     */
    public function transform(array $attributes)
    {
        $result = [];
        foreach ($attributes as $attribute => $values) {
            $config = array_merge([], $this->config);
            $config['subject'] = $attribute;
            $filter = new \SimpleSAML\Module\core\Auth\Process\AttributeAlter($config, null);
            $request = [self::ATTRIBUTES_KEY => [$attribute => $values]];
            $filter->process($request);
            $result[$attribute] = $request[self::ATTRIBUTES_KEY][$attribute] ?? null;
        }
        return $result;
    }
}
