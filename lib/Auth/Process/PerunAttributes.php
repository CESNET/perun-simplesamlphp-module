<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * Class PerunAttributes
 *
 * This filter fetches user attributes by its names listed as keys of attrMap config property
 * and set them as Attributes values to keys specified as attrMap values. Old values of Attributes are replaced.
 *
 * It strongly relays on PerunIdentity filter to obtain perun user id. Configure it before this filter properly.
 *
 * if attribute in Perun value is null or is not set at all SSP attribute is set to empty array.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class PerunAttributes extends \SimpleSAML\Auth\ProcessingFilter
{
    private $attrMap;
    private $interface;
    private $mode;

    const MODE_FULL = 'FULL';
    const MODE_PARTIAL = 'PARTIAL';
    /**
     * @var Adapter
     */
    private $adapter;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert('is_array($config)');

        if (!isset($config['attrMap'])) {
            throw new Exception(
                "perun:PerunAttributes: missing mandatory configuration option 'attrMap'."
            );
        }
        if (!isset($config['interface'])) {
            $config['interface'] = Adapter::RPC;
        }

        if (!isset($config['mode'])) {
            $config['mode'] = self::MODE_FULL;
        }

        $this->attrMap = (array)$config['attrMap'];
        $this->interface = (string)$config['interface'];
        $this->mode = (string)$config['mode'];
        if (!in_array($this->mode, [self::MODE_FULL, self::MODE_PARTIAL])) {
            $this->mode = self::MODE_FULL;
        }
        $this->adapter = Adapter::getInstance($this->interface);
    }

    public function process(&$request)
    {
        assert('is_array($request)');

        if (isset($request['perun']['user'])) {
            $user = $request['perun']['user'];
        } else {
            throw new Exception(
                "perun:PerunAttributes: " .
                "missing mandatory field 'perun.user' in request." .
                "Hint: Did you configured PerunIdentity filter before this filter?"
            );
        }

        $attributes = [];
        if ($this->mode === self::MODE_FULL) {
            $attributes = array_keys($this->attrMap);
        } elseif ($this->mode === self::MODE_PARTIAL) {
            // Check if attribute has some value
            foreach ($this->attrMap as $attrName => $attrValue) {
                if (isset($request['Attributes'][$attrValue])) {
                    $attr = $request['Attributes'][$attrValue];
                    if ($attr === null || empty($attr)) {
                        array_push($attributes, $attrName);
                    }
                } else {
                    array_push($attributes, $attrName);
                }
            }
        }


        $attrs = $this->adapter->getUserAttributes($user, $attributes);

        foreach ($attrs as $attrName => $attrValue) {
            $sspAttr = $this->attrMap[$attrName];

            // convert $attrValue into array
            if ($attrValue === null) {
                $value = [];
            } elseif (is_string($attrValue) || is_numeric($attrValue)) {
                $value = [$attrValue];
            } elseif ($this->hasStringKeys($attrValue)) {
                $value = $attrValue;
            } elseif (is_array($attrValue)) {
                $value = $attrValue;
            } else {
                throw new Exception(
                    "sspmod_perun_Auth_Process_PerunAttributes - Unsupported attribute type. "
                    .
                    "Attribute name: $attrName, Supported types: null, string, numeric, array, associative array."
                );
            }

            // convert $sspAttr into array
            if (is_string($sspAttr)) {
                $attrArray = [$sspAttr];
            } elseif (is_array($sspAttr)) {
                $attrArray = $sspAttr;
            } else {
                throw new Exception(
                    "sspmod_perun_Auth_Process_PerunAttributes - Unsupported attribute type. " .
                    "Attribute \$attrName, Supported types: string, array."
                );
            }

            Logger::debug("perun:PerunAttributes: perun attribute $attrName was fetched. " .
                "Value " . implode(",", $value) .
                " is being setted to ssp attributes " . implode(",", $attrArray));


            // write $value to all SP attributes
            foreach ($attrArray as $attribute) {
                $request['Attributes'][$attribute] = $value;
            }
        }
    }

    private function hasStringKeys($array)
    {
        if (!is_array($array)) {
            return false;
        }
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }
}
