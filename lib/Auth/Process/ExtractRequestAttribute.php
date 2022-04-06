<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\PerunConstants;

/**
 * Extracts request variable value specified by chain of keys into the configured destination attribute.
 */
class ExtractRequestAttribute extends ProcessingFilter
{
    public const STAGE = 'perun:ExtractRequestAttribute';
    public const DEBUG_PREFIX = self::STAGE . ' - ';

    public const DESTINATION_ATTRIBUTE_NAME = 'destination_attribute_name';
    public const REQUEST_KEYS = 'request_keys';
    public const FAIL_ON_NON_EXISTING_KEY = 'fail_on_not_existing_key';
    public const DEFAULT_VALUE = 'default_value';

    public const KEYS_SEPARATOR = ';';
    public const FAILURE_VALUE = ['%$FAILURE_VALUE$%'];

    private $destinationAttrName;
    private $requestKeys;
    private $failOnNonExistingKey;
    private $defaultValue;
    private $filterConfig;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $this->filterConfig = Configuration::loadFromArray($config);

        $this->destinationAttrName = $this->filterConfig->getString(self::DESTINATION_ATTRIBUTE_NAME, null);
        if (empty($this->destinationAttrName)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'missing mandatory configuration for option \'' . self::DESTINATION_ATTRIBUTE_NAME . '\''
            );
        }

        $this->requestKeys = $this->filterConfig->getString(self::REQUEST_KEYS, null);
        if (empty($this->requestKeys)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'missing mandatory configuration for option \'' . self::REQUEST_KEYS . '\''
            );
        }
        $this->requestKeys = explode(self::KEYS_SEPARATOR, $this->requestKeys);

        $this->failOnNonExistingKey = $this->filterConfig->getBoolean(self::FAIL_ON_NON_EXISTING_KEY, true);
        $this->defaultValue = $this->filterConfig->getArray(self::DEFAULT_VALUE, self::FAILURE_VALUE);
        if (
            !$this->failOnNonExistingKey
            && self::FAILURE_VALUE === $this->defaultValue
        ) {
            throw new Exception(
                self::DEBUG_PREFIX . 'invalid configuration, fail on missing key is disabled, but no default value ' . 'for the attribute has been set'
            );
        }
    }

    public function process(&$request)
    {
        assert(is_array($request));

        $value = $request;
        foreach ($this->requestKeys as $key) {
            if (is_numeric($key)) {
                $key = (int) $key;
            }
            if (!array_key_exists($key, $value)) {
                Logger::warning(
                    self::DEBUG_PREFIX . 'Cannot find key \'' . $key . '\' in the supposed path towards the value. Did you configure the right path of keys to extract it?'
                );
                if ($this->failOnNonExistingKey) {
                    throw new Exception(self::DEBUG_PREFIX . 'Specified chain of keys does not exist');
                }
                $value = $this->defaultValue;
                break;
            }
            $value = $value[$key];
        }

        if (self::FAILURE_VALUE === $value) {
            throw new Exception(self::DEBUG_PREFIX . 'Value cannot be extracted');
        }

        if (!array_key_exists(PerunConstants::ATTRIBUTES, $request)) {
            $request[PerunConstants::ATTRIBUTES] = [];
        }
        if (!is_array($value) || !is_object($value)) {
            $value = [$value];
        }
        $request[PerunConstants::ATTRIBUTES][$this->destinationAttrName] = $value;
        $logValue = implode(',', $value);
        Logger::debug(
            self::DEBUG_PREFIX . 'Value \'' . $logValue . '\' has been extracted and set to attribute ' . $this->destinationAttrName
        );
    }
}
