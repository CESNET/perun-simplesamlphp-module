<?php

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Get lists of certificates per purpose.
 */
class KeyLists implements AttributeTransformer
{
    private $purpose2internal;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->purpose2internal = $config->getArray('purpose2internal');
    }

    /**
     * @override
     */
    public function transform(array $attributes)
    {
        if (count($attributes) !== 1) {
            throw new \Exception('KeyLists transformer only works with 1 attribute.');
        }
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
