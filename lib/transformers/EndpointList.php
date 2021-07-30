<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\SingularAttributeTransformer;

/**
 * Get list(s) of endpoints of selected type.
 */
class EndpointList extends SingularAttributeTransformer
{
    public const BINDING_PREFIX = 'urn:oasis:names:tc:SAML:2.0:bindings:';

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
    public function singleTransform($values)
    {
        $endpoints = $values;
        if (empty($endpoints)) {
            return null;
        }
        if (! is_array($endpoints)) {
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

    /**
     * @override
     */
    public function singleDescription(string $description)
    {
        return sprintf('endpoints with type %s from (%s)', $this->binding, $description);
    }
}
