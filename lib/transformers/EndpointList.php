<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Get list(s) of endpoints of selected type.
 */
class EndpointList implements AttributeTransformer
{
    const BINDING_PREFIX = 'urn:oasis:names:tc:SAML:2.0:bindings:';

    private $binding;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->binding = $config->getString('binding');
        if (strpos($this->binding, self::BINDING_PREFIX) !== 0) {
            $this->binding = self::BINDING_PREFIX . $this->binding;
        }
    }

    /**
     * @override
     */
    public function transform($attributes)
    {
        $result = [];
        foreach ($attributes as $attribute => $value) {
            $result[$attribute] = $this->getEndpoint($value);
        }
        return $result;
    }

    private function getEndpoint($endpoints)
    {
        if (empty($endpoints)) {
            return null;
        }
        if (!is_array($endpoints)) {
            return [$endpoints];
        }
        $result = [];
        foreach ($endpoints as $endpoint) {
            if ($endpoint['Binding'] === $this->binding) {
                $result[] = $endpoint['Location'];
            }
        }
        return $result;
    }
}
