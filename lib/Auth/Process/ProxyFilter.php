<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Configuration;

/**
 * Class sspmod_perun_Auth_Process_ProxyFilter
 *
 * This filter allows to disable/enable nested filter for particular SP
 * or for users with one of (black/white)listed attribute values.
 * Based on the mode of operation, the nested filter
 * IS (whitelist) or IS NOT (blacklist) run when any of the attribute values matches.
 * SPs are defined by theirs entityID in property 'filterSPs'.
 * User attributes are defined as a map 'attrName'=>['value1','value2']
 * in property 'filterAttributes'.
 * Nested filter is defined in property config as regular filter.
 *
 * example usage:
 *
 * 10 => [
 *        'class' => 'perun:ProxyFilter',
 *        'filterSPs' => ['disableSpEntityId01', 'disableSpEntityId02'],
 *        'filterAttributes' => [
 *            'eduPersonPrincipalName' => ['test@example.com'],
 *            'eduPersonAffiliation' => ['affiliate','member'],
 *        ],
 *        'config' => [
 *            'class' => 'perun:NestedFilter',
 *            // ...
 *        ],
 * ],
 * 20 => [
 *        'class' => 'perun:ProxyFilter',
 *        'mode' => 'whitelist',
 *        'filterSPs' => ['enableSpEntityId01', 'enableSpEntityId02'],
 *        'config' => [
 *            'class' => 'perun:NestedFilter',
 *            // ...
 *        ],
 * ],
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class ProxyFilter extends \SimpleSAML\Auth\ProcessingFilter
{
    public const MODE_BLACKLIST = 'blacklist';
    public const MODE_WHITELIST = 'whitelist';
    public const MODES = [
        self::MODE_BLACKLIST,
        self::MODE_WHITELIST,
    ];

    private $config;
    private $nestedClass;
    private $filterSPs;
    private $filterAttributes;
    private $mode;
    private $reserved;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        $conf = Configuration::loadFromArray($config);
        $this->config = $conf->getArray('config', []);
        $this->nestedClass = Configuration::loadFromArray($this->config)->getString('class');
        unset($this->config['class']);
        $this->filterSPs = $conf->getArray('filterSPs', []);
        $this->filterAttributes = $conf->getArray('filterAttributes', []);
        $this->mode = $conf->getValueValidate('mode', self::MODES, self::MODE_BLACKLIST);

        $this->reserved = (array)$reserved;
    }

    public function process(&$request)
    {
        assert(is_array($request));

        $default = $this->mode === self::MODE_BLACKLIST;
        $shouldRun = $this->shouldRunForSP($request['Destination']['entityid'], $default);
        if ($shouldRun === $default) {
            $shouldRun = $this->shouldRunForAttribute($request['Attributes'], $default);
        }

        if ($shouldRun) {
            $this->runAuthProcFilter($request);
        }
    }

    private function shouldRunForSP($currentSp, $default)
    {
        foreach ($this->filterSPs as $sp) {
            if ($sp === $currentSp) {
                $shouldRun = !$default;
                Logger::info(
                    sprintf(
                        'perun.ProxyFilter: %s filter %s for SP %s',
                        $shouldRun ? 'Running' : 'Filtering out',
                        $this->nestedClass,
                        $currentSp
                    )
                );
                return $shouldRun;
            }
        }
        return $default;
    }

    private function shouldRunForAttribute($attributes, $default)
    {
        foreach ($this->filterAttributes as $attr => $values) {
            if (isset($attributes[$attr]) && is_array($attributes[$attr])) {
                foreach ($values as $value) {
                    if (in_array($value, $attributes[$attr])) {
                        $shouldRun = !$default;
                        Logger::info(
                            sprintf(
                                'perun.ProxyFilter: %s filter %s because %s contains %s',
                                $shouldRun ? 'Running' : 'Filtering out',
                                $this->nestedClass,
                                $attr,
                                $value
                            )
                        );
                        return $shouldRun;
                    }
                }
            }
        }
        return $default;
    }

    private function runAuthProcFilter(&$request)
    {
        list($module, $simpleClass) = explode(':', $this->nestedClass);
        $className = '\SimpleSAML\Module\\' . $module . '\Auth\Process\\' . $simpleClass;
        $authFilter = new $className($this->config, $this->reserved);
        $authFilter->process($request);
    }
}
