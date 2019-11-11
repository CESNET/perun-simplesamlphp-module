<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

class KeyListsToArray implements AttributeTransformer
{
    public function __construct()
    {
    }

    public function transform($attributes, $config)
    {
        $purposes = $config['purposes'] ?? [];
        $removeSource = $config['keepSource'] ?? true;
        $purposesValues = array_values($purposes);
        $result = [];
        $keys = [];
        foreach ($attributes as $attribute => $value) {
            if ($removeSource) {
                $result[$attribute] = null;
            }
            if (!empty($value) && isset($purposes[$attribute])) {
                $purpose = $purposes[$attribute];
                foreach ($value as $key) {
                    if (!isset($keys[$key])) {
                        $keys[$key] = array_fill_keys($purposesValues, false);
                    }
                    $keys[$key][$purpose] = true;
                }
            }
        }
        // no keys
        if (empty($keys)) {
            return $result;
        }
        // one key for everything (certData)
        if (
            !empty($config['outputCertData']) && count($keys) === 1
            && count(array_filter(current($keys))) === count($purposes)
        ) {
            return array_merge($result, [$config['outputCertData'] => key($keys)]);
        }
        // keys array
        $attrName = $config['outputKeys'] ?? 'keys';
        return array_merge($result, [$attrName => self::formatKeys($keys)]);
    }

    private static function formatKeys(array $keys)
    {
        return array_map(function ($key, $purposes) {
            return array_merge($purposes, [
                'type' => 'X509Certificate',
                'X509Certificate' => $key,
            ]);
        }, array_keys($keys), $keys);
    }
}
