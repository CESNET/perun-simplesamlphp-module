<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\PerunConstants;

/**
 * Drop specified user attributes.
 */
class DropUserAttributes extends ProcessingFilter
{
    public const STAGE = 'perun:DropUserAttributes';
    public const DEBUG_PREFIX = self::STAGE . ' - ';

    public const ATTRIBUTE_NAMES = 'attribute_names';

    private $attributeNames;
    private $filterConfig;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $this->filterConfig = Configuration::loadFromArray($config);

        $this->attributeNames = $this->filterConfig->getArray(self::ATTRIBUTE_NAMES, []);
        if (empty($this->attributeNames)) {
            Logger::warning(
                self::DEBUG_PREFIX . 'Invalid configuration: no name of attributes to be dropped has '
                . 'been configured. Use option \'' . self::ATTRIBUTE_NAMES . '\' to configure the name of the attribute.'
            );
        }
    }

    public function process(&$request)
    {
        assert(is_array($request));
        if (empty($this->attributeNames)) {
            Logger::warning(
                self::DEBUG_PREFIX . 'List of attribute names which should be dropped is empty. Skip processing.'
            );
        } elseif (empty($request[PerunConstants::ATTRIBUTES])) {
            Logger::warning(self::DEBUG_PREFIX . 'There are no attributes in the request. Skip processing.');

            return;
        }

        $attributes = &$request[PerunConstants::ATTRIBUTES];
        foreach ($this->attributeNames as $attributeName) {
            if (isset($attributes[$attributeName])) {
                unset($attributes[$attributeName]);
                Logger::debug(self::DEBUG_PREFIX . 'Removed attribute \'' . $attributeName . '\'.');
            }
        }
    }
}
