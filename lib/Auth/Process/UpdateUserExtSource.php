<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * Class sspmod_perun_Auth_Process_UpdateUserExtSource
 *
 * This filter updates userExtSource attributes when he logs in.
 *
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class UpdateUserExtSource extends \SimpleSAML\Auth\ProcessingFilter
{
    private $attrMap;
    private $attrsToConversion;
    private $adapter;
    const UES_ATTR_NMS = 'urn:perun:ues:attribute-def:def:';

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert('is_array($config)');

        if (!isset($config['attrMap'])) {
            throw new Exception(
                "perun:UpdateUserExtSource: missing mandatory configuration option 'attrMap'."
            );
        }

        if (isset($config['arrayToStringConversion'])) {
            $this->attrsToConversion = (array)$config['arrayToStringConversion'];
        } else {
            $this->attrsToConversion = array();
        }

        $this->attrMap = (array)$config['attrMap'];
        $this->adapter = Adapter::getInstance(Adapter::RPC);
    }

    public function process(&$request)
    {
        assert('is_array($request)');
        try {
            $userExtSource = $this->adapter->getUserExtSource(
                $request['Attributes']['sourceIdPEntityID'][0],
                $request['Attributes']['sourceIdPEppn'][0]
            );
            if (is_null($userExtSource)) {
                throw new Exception(
                    "sspmod_perun_Auth_Process_UpdateUserExtSource: there is no UserExtSource with ExtSource " .
                    $request['Attributes']['sourceIdPEntityID'][0] . " and Login " .
                    $request['Attributes']['sourceIdPEppn'][0]
                );
            }

            $attributes = $this->adapter->getUserExtSourceAttributes($userExtSource['id'], array_keys($this->attrMap));

            if (is_null($attributes)) {
                throw new Exception(
                    "sspmod_perun_Auth_Process_UpdateUserExtSource: getting attributes was not successful."
                );
            }

            $attributesToUpdate = array();
            foreach ($attributes as $attribute) {
                $attrName = self::UES_ATTR_NMS . $attribute['friendlyName'];
                if (isset($this->attrMap[$attrName]) && isset($request['Attributes'][$this->attrMap[$attrName]])) {
                    $attr = $request['Attributes'][$this->attrMap[$attrName]];

                    if (in_array(self::UES_ATTR_NMS . $attribute['friendlyName'], $this->attrsToConversion)) {
                        $arrayAsString = array('');
                        foreach ($attr as $value) {
                            $arrayAsString[0] .= $value . ';';
                        }
                        if (!empty($arrayAsString[0])) {
                            $arrayAsString[0] = substr($arrayAsString[0], 0, -1);
                        }
                        $attr = $arrayAsString;
                    }

                    if (strpos($attribute['type'], 'String') ||
                        strpos($attribute['type'], 'Integer') ||
                        strpos($attribute['type'], 'Boolean')) {
                        $valueFromIdP = $attr[0];
                    } elseif (strpos($attribute['type'], 'Array') || strpos($attribute['type'], 'Map')) {
                        $valueFromIdP = $attr;
                    } else {
                        throw new Exception(
                            'sspmod_perun_Auth_Process_UpdateUserExtSource: unsupported type of attribute.'
                        );
                    }
                    if ($valueFromIdP !== $attribute['value']) {
                        $attribute['value'] = $valueFromIdP;
                        array_push($attributesToUpdate, $attribute);
                    }
                }

            }

            if (!empty($attributesToUpdate)) {
                $this->adapter->setUserExtSourceAttributes($userExtSource['id'], $attributesToUpdate);
            }
            $this->adapter->updateUserExtSourceLastAccess($userExtSource['id']);
        } catch (\Exception $ex) {
            Logger::warning(
                "sspmod_perun_Auth_Process_UpdateUserExtSource: update was not successful: " .
                $ex->getMessage() . " Skip to next filter."
            );
        }
    }
}
