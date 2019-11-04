<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

class KeyLists implements AttributeTransformer
{
    private $purpose2internal;

    public function __construct()
    {
    }

    public function transform($attributes, $config)
    {
        if (count($attributes) !== 1) {
            throw new \Exception('KeyLists transformer only works with 1 attribute.');
        }
        $this->purpose2internal = $config['purpose2internal'];
        foreach ($attributes as $keys) {
            return $this->getCertData($keys);
        }
    }

    private function getCertData(array $keys)
    {
        $attributes = [];
        foreach ($this->purpose2internal as $internal) {
            $attributes[$internal] = [];
        }
        foreach ($keys as $key) {
            if ($key['type'] === 'X509Certificate' && !empty($key['X509Certificate'])) {
                foreach ($this->purpose2internal as $purpose => $internal) {
                    if ($key[$purpose]) {
                        $attributes[$internal][] = $key['X509Certificate'];
                    }
                }
            }
        }
        return $attributes;
    }
}
