<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

class AttributeUtils
{
    public const CONFIG_FILE_NAME = 'perun_attributes.php';

    public const INTERNAL_ATTR_NAME = 'internalAttrName';

    public const TYPE = 'type';

    public const LDAP = 'ldap';

    public const RPC = 'rpc';

    public static function getLdapAttrName($internalAttrName)
    {
        return self::getAttrName($internalAttrName, self::LDAP);
    }

    public static function getRpcAttrName($internalAttrName)
    {
        return self::getAttrName($internalAttrName, self::RPC);
    }

    public static function createLdapAttrNameTypeMap($internalAttrName)
    {
        return self::createAttrNameTypeMap($internalAttrName, self::LDAP);
    }

    public static function createRpcAttrNameTypeMap($internalAttrName)
    {
        return self::createAttrNameTypeMap($internalAttrName, self::RPC);
    }

    public static function getRpcAttrNames($internalAttrNames)
    {
        return self::getAttrNames($internalAttrNames, self::RPC);
    }

    public static function getLdapAttrNames($internalAttrNames)
    {
        return self::getAttrNames($internalAttrNames, self::LDAP);
    }

    public static function getAttrName($internalAttrName, $interface)
    {
        $perunAttributesConfig = self::getConfig();
        $resultAttrName = null;

        try {
            $attrArray = $perunAttributesConfig->getArray($internalAttrName);

            if (array_key_exists($interface, $attrArray)) {
                $resultAttrName = $attrArray[$interface];
            }
        } catch (\Exception $ex) {
            Logger::warning(
                'perun:AttributeUtils: missing ' . $internalAttrName . ' attribute in perun_attributes.php file'
            );
        }

        return $resultAttrName;
    }

    public static function createAttrNameTypeMap($internalAttrNames, $interface)
    {
        $perunAttributesConfig = self::getConfig();
        $resultArray = [];

        foreach ($internalAttrNames as $internalAttrName) {
            try {
                $attrArray = $perunAttributesConfig->getArray($internalAttrName);

                if (array_key_exists($interface, $attrArray)) {
                    $resultArray[$attrArray[$interface]] = [
                        self::INTERNAL_ATTR_NAME => $internalAttrName,
                        self::TYPE => $attrArray[self::TYPE],
                    ];
                }
            } catch (\Exception $ex) {
                Logger::warning(
                    'perun:AttributeUtils: missing ' . $internalAttrName . ' attribute in perun_attributes.php file'
                );
            }
        }

        return $resultArray;
    }

    public static function getAttrNames($internalAttrNames, $interface)
    {
        $perunAttributesConfig = self::getConfig();
        $resultArray = [];

        foreach ($internalAttrNames as $internalAttrName) {
            try {
                $attrArray = $perunAttributesConfig->getArray($internalAttrName);

                if (array_key_exists($interface, $attrArray)) {
                    $resultArray[$attrArray[$interface]] = $internalAttrName;
                }
            } catch (\Exception $ex) {
                Logger::warning(
                    'perun:AttributeUtils: missing ' . $internalAttrName . ' attribute in perun_attributes.php file'
                );
            }
        }

        return $resultArray;
    }

    private static function getConfig()
    {
        $perunAttributesConfig = null;

        try {
            $perunAttributesConfig = Configuration::getConfig(self::CONFIG_FILE_NAME);
        } catch (\Exception $ex) {
            Logger::warning('perun:AttributeUtils: missing or invalid perun_attributes.php config file');
        }

        if ($perunAttributesConfig === null) {
            throw new Exception('perun:AttributeUtils: missing or invalid perun_attributes.php config file');
        }

        return $perunAttributesConfig;
    }
}
