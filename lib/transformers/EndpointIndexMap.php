<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Get map of binding to comma separated list of endpoints with the option to keep original indexes.
 */
class EndpointIndexMap extends AttributeTransformer
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
    public function transform(array $attributes)
    {
        if (count($attributes) !== 2) {
            throw new \Exception(
                'Invalid configuration of EndpointIndexMap transformer, exactly 2 input attributes exptected'
            );
        }
        list($endpoints, $keepIndexes) = array_values($attributes);
        $names = array_keys($attributes);
        $attributeName = $names[0];
        if (is_bool($endpoints)) {
            $tmp = $keepIndexes;
            $keepIndexes = $endpoints;
            $endpoints = $tmp;
            $attributeName = $names[1];
        }
        if (empty($endpoints)) {
            $result = null;
        } else {
            if (! is_array($endpoints)) {
                $endpoints = [[
                    'Location' => $endpoints,
                ]];
            }
            $result = [];
            foreach ($endpoints as $endpoint) {
                $binding = $endpoint['Binding'] ?: $this->defaultBinding;
                if (! $this->fullNames) {
                    $binding = str_replace(self::BINDING_PREFIX, '', $binding);
                }
                if (! isset($result[$binding])) {
                    $result[$binding] = [];
                }

                $result[$binding][$endpoint['index']] = $endpoint['Location'];
            }
            if ($keepIndexes) {
                $result = array_map(function ($endpoints) {
                    $e = [];
                    for ($i = 0; $i <= max(array_keys($endpoints)); $i++) {
                        $e[] = isset($endpoints[$i]) ? $endpoints[$i] : '';
                    }
                    return implode(self::MAPLIST_SEPARATOR, $e);
                }, $result);
            } else {
                $result = array_map(function ($endpoints) {
                    return implode(self::MAPLIST_SEPARATOR, $endpoints);
                }, $result);
            }
        }
        return [
            $attributeName => $result,
        ];
    }

    /**
     * @override
     */
    public function getDescription(array $attributes)
    {
        $descriptions = array_values($attributes);
        $names = array_keys($attributes);
        return [
            $names[0] => sprintf('comma-separated lists of Locations per Bindings from (%s)', $descriptions[0]),
            $names[1] => '',
        ];
    }
}
