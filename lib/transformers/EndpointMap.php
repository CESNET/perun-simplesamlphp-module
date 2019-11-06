<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

class EndpointMap implements AttributeTransformer
{
    const MAPLIST_SEPARATOR = ',';

    const BINDING_PREFIX = 'urn:oasis:names:tc:SAML:2.0:bindings:';

    public function __construct()
    {
    }

    public function transform($attributes, $config)
    {
        $defaultBinding = $config['defaultBinding'];
        $fullNames = empty($config['shortNames']);
        $result = [];
        foreach ($attributes as $attribute => $value) {
            $result[$attribute] = self::getEndpointsMap($value, $defaultBinding, $fullNames);
        }
        return $result;
    }

    private static function getEndpointsMap($endpoints, string $defaultBinding, $fullNames = true)
    {
        if (empty($endpoints)) {
            return null;
        }
        if (!is_array($endpoints)) {
            return [$defaultBinding => $endpoints];
        }
        $result = [];
        foreach ($endpoints as $endpoint) {
            $binding = $endpoint['Binding'] ?: $defaultBinding;
            if (!$fullNames) {
                $binding = str_replace(self::BINDING_PREFIX, '', $binding);
            }
            if (!isset($result[$binding])) {
                $result[$binding] = $endpoint['Location'];
            } else {
                $result[$binding] .= self::MAPLIST_SEPARATOR . $endpoint['Location'];
            }
        }
        return $result;
    }
}
