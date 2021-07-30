<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\SingularAttributeTransformer;

/**
 * Get map of binding to comma separated list of endpoints.
 */
class EndpointMap extends SingularAttributeTransformer
{
    public const MAPLIST_SEPARATOR = ',';

    public const BINDING_PREFIX = 'urn:oasis:names:tc:SAML:2.0:bindings:';

    private $defaultBinding;

    private $fullNames;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->defaultBinding = $config->getString('defaultBinding');
        $this->fullNames = ! $config->getBoolean('shortNames', false);
    }

    /**
     * @override
     */
    public function singleTransform($values)
    {
        $endpoints = $values;
        if (empty($endpoints)) {
            return null;
        }
        if (! is_array($endpoints)) {
            return [
                $this->defaultBinding => $endpoints,
            ];
        }
        $result = [];
        foreach ($endpoints as $endpoint) {
            $binding = $endpoint['Binding'] ?: $this->defaultBinding;
            if (! $this->fullNames) {
                $binding = str_replace(self::BINDING_PREFIX, '', $binding);
            }
            if (! isset($result[$binding])) {
                $result[$binding] = $endpoint['Location'];
            } else {
                $result[$binding] .= self::MAPLIST_SEPARATOR . $endpoint['Location'];
            }
        }
        return $result;
    }

    /**
     * @override
     */
    public function singleDescription(string $description)
    {
        return sprintf('comma-separated lists of Locations per Bindings from (%s)', $description);
    }
}
