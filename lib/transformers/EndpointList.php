<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

class EndpointList implements AttributeTransformer
{
    const BINDING_PREFIX = 'urn:oasis:names:tc:SAML:2.0:bindings:';

    public function __construct()
    {
    }

    public function transform($attributes, $config)
    {
        $binding = $config['binding'];
        $result = [];
        foreach ($attributes as $attribute => $value) {
            $result[$attribute] = self::getEndpoint($value, $binding);
        }
        return $result;
    }

    private static function getEndpoint($endpoints, string $binding)
    {
        if (empty($endpoints)) {
            return null;
        }
        if (!is_array($endpoints)) {
            return [$endpoints];
        }
        $result = [];
        if (strpos($binding, self::BINDING_PREFIX) !== 0) {
            $binding = self::BINDING_PREFIX . $binding;
        }
        foreach ($endpoints as $endpoint) {
            if ($endpoint['Binding'] === $binding) {
                $result[] = $endpoint['Location'];
            }
        }
        return $result;
    }
}
