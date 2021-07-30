<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandler;

/**
 * Class IdPAttribute
 *
 * This class for each line in $attrMAp search the $key in IdP Metadata and save it to $request['Attributes'][$value]
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class IdPAttribute extends \SimpleSAML\Auth\ProcessingFilter
{
    private $attrMap;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert(is_array($config));

        if (! isset($config['attrMap'])) {
            throw new Exception('perun:IdPAttribute: missing mandatory configuration option \'attrMap\'.');
        }

        $this->attrMap = (array) $config['attrMap'];
    }

    public function process(&$request)
    {
        assert(is_array($request));

        $metadataHandler = MetaDataStorageHandler::getMetadataHandler();
        $sourceIdpMeta = $metadataHandler->getMetaData($request['saml:sp:IdP'], 'saml20-idp-remote');

        foreach ($this->attrMap as $attributeKey => $attributeValue) {
            $attributeNames = preg_split('/:/', $attributeKey);
            $key = array_shift($attributeNames);

            if (! isset($sourceIdpMeta[$key])) {
                continue;
            }
            $value = $sourceIdpMeta[$key];

            foreach ($attributeNames as $attributeName) {
                if (! isset($value[$attributeName])) {
                    continue;
                }
                $value = $value[$attributeName];
            }

            if (! is_array($value)) {
                $value = [$value];
            }

            if (! empty($value)) {
                $request['Attributes'][$attributeValue] = $value;
            }
        }
    }
}
