<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Get SSP keys array from key lists.
 */
class KeyListsToArray implements AttributeTransformer
{
    private $purposes;

    private $removeSource;

    private $purposesValues;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->purposes = $config->getArray('purposes');
        $this->removeSource = !$config->getBoolean('keepSource', true);
        $this->purposesValues = array_values($this->purposes);
    }

    /**
     * @override
     */
    public function transform(array $attributes)
    {
        $result = [];
        $keys = [];
        foreach ($attributes as $attribute => $value) {
            if ($this->removeSource) {
                $result[$attribute] = null;
            }
            if (!empty($value) && isset($this->purposes[$attribute])) {
                $purpose = $this->purposes[$attribute];
                foreach ($value as $key) {
                    if (!isset($keys[$key])) {
                        $keys[$key] = array_fill_keys($this->purposesValues, false);
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
            && count(array_filter(current($keys))) === count($this->purposes)
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
