<?php

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Get SSP endpoint array from endpoint map.
 */
class EndpointMapToArray extends AttributeTransformer
{
    const MAPLIST_SEPARATOR = ',';

    const BINDING_PREFIX = 'urn:oasis:names:tc:SAML:2.0:bindings:';

    const INDEX_MIN = 0;

    private $defaultBinding;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->defaultBinding = $this->getBindingName($config->getString('defaultBinding'));
    }

    /**
     * @override
     */
    public function transform(array $attributes)
    {
        $result = [];
        foreach ($attributes as $attribute => $value) {
            $result[$attribute] = $this->getEndpointsArray($value);
        }
        return $result;
    }

    private function getBindingName($binding)
    {
        if (strpos($binding, 'urn:') !== 0) {
            return self::BINDING_PREFIX . $binding;
        }
        return $binding;
    }

    /**
     * @see https://simplesamlphp.org/docs/stable/simplesamlphp-metadata-endpoints
     */
    private function getEndpointsArray($endpointMap)
    {
        if (empty($endpointMap) || !is_array($endpointMap)) {
            return null;
        }

        // if all endpoints use the default binding
        if (count($endpointMap) === 1 && isset($endpointMap[$this->defaultBinding])) {
            $result = explode(self::MAPLIST_SEPARATOR, $endpointMap[$this->defaultBinding]);
            return count($result) === 1 ? $result[0] : $result;
        }

        $result = [];
        $index = self::INDEX_MIN;
        // prefer default binding
        if (isset($endpointMap[$this->defaultBinding])) {
            foreach (explode(self::MAPLIST_SEPARATOR, $endpointMap[$this->defaultBinding]) as $location) {
                $result[] = $this->getEndpoint($location, $this->defaultBinding, $index++, $index === self::INDEX_MIN);
            }
        }
        foreach ($endpointMap as $binding => $locations) {
            if ($binding !== $this->defaultBinding) {
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
