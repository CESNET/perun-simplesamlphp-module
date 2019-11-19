<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

class EndpointMap implements AttributeTransformer
{
    const MAPLIST_SEPARATOR = ',';

    const BINDING_PREFIX = 'urn:oasis:names:tc:SAML:2.0:bindings:';

    private $defaultBinding;

    private $fullNames;

    public function __construct($config)
    {
        $this->defaultBinding = $config['defaultBinding'];
        $this->fullNames = empty($config['shortNames']);
    }

    public function transform($attributes)
    {
        $result = [];
        foreach ($attributes as $attribute => $value) {
            $result[$attribute] = $this->getEndpointsMap($value);
        }
        return $result;
    }

    private function getEndpointsMap($endpoints)
    {
        if (empty($endpoints)) {
            return null;
        }
        if (!is_array($endpoints)) {
            return [$this->defaultBinding => $endpoints];
        }
        $result = [];
        foreach ($endpoints as $endpoint) {
            $binding = $endpoint['Binding'] ?: $this->defaultBinding;
            if (!$this->fullNames) {
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