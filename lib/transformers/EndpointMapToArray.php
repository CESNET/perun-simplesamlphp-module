<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

class EndpointMapToArray implements AttributeTransformer
{
    const MAPLIST_SEPARATOR = ',';

    const BINDING_PREFIX = 'urn:oasis:names:tc:SAML:2.0:bindings:';

    const INDEX_MIN = 0;

    private $fullNames = true;

    public function __construct()
    {
    }

    public function transform($attributes, $config)
    {
        $defaultBinding = $config['defaultBinding'];
        $this->fullNames = empty($config['shortNames']);
        $result = [];
        foreach ($attributes as $attribute => $value) {
            $result[$attribute] = $this->getEndpointsArray($value, $defaultBinding, $fullNames);
        }
        return $result;
    }

    private function getBindingName($binding)
    {
        if ($this->fullNames && strpos($binding, self::BINDING_PREFIX) !== 0) {
            return self::BINDING_PREFIX . $binding;
        } elseif (!$this->fullNames && strpos($binding, self::BINDING_PREFIX) === 0) {
            return str_replace(self::BINDING_PREFIX, '', $binding);
        }
        return $binding;
    }

    private function getEndpointsArray($endpointMap, string $defaultBinding)
    {
        if (empty($endpointMap) || !is_array($endpointMap)) {
            return null;
        }

        $defaultBinding = $this->getBindingName($defaultBinding);

        // if all endpoints use the default binding
        if (count($endpointMap) === 1 && isset($endpointMap[$defaultBinding])) {
            $result = explode(self::MAPLIST_SEPARATOR, $endpointMap[$defaultBinding]);
            return count($result) === 1 ? $result[0] : $result;
        }

        $result = [];
        $index = self::INDEX_MIN;
        // prefer default binding
        if (isset($endpointMap[$defaultBinding])) {
            foreach (explode(self::MAPLIST_SEPARATOR, $endpointMap[$defaultBinding]) as $location) {
                $result[] = $this->getEndpoint($location, $defaultBinding, $index++, $index === self::INDEX_MIN);
            }
        }
        foreach ($endpointMap as $binding => $locations) {
            if ($binding !== $defaultBinding) {
                foreach (explode(self::MAPLIST_SEPARATOR, $locations) as $location) {
                    $result[] = $this->getEndpoint($location, $binding, $index++);
                }
            }
        }

        return $result;
    }

    private function getEndpoint($location, $binding, $index, $isDefault = false)
    {
        $result = [
            'index' => $index,
            'Location' => $location,
            'Binding' => $this->getBindingName($binding),
        ];
        if ($isDefault) {
            $result['isDefault'] = $isDefault;
        }
        return $result;
    }
}
