<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Get lists of certificates per purpose.
 */
class KeyLists extends AttributeTransformer
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

    public function getDescription(array $attributes)
    {
        $description = null;
        foreach ($attributes as $desc) {
            $description = $desc;
            break;
        }
        $descriptions = [];
        foreach ($this->purpose2internal as $purpose => $internal) {
            $descriptions[$internal] = sprintf('X509Certificate with %s=true from (%s)', $purpose, $description);
        }
        return $descriptions;
    }

    private function getCertData(array $keys)
    {
        $attributes = [];
        foreach ($this->purpose2internal as $internal) {
            $attributes[$internal] = [];
        }
        foreach ($keys as $key) {
            if ($key['type'] === 'X509Certificate' && ! empty($key['X509Certificate'])) {
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
