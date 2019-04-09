<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * Class sspmod_perun_Auth_Process_ProxyFilter
 *
 * This filter allows to disable nested filter for particular SP.
 * SPs are defined by theirs entityID in property 'filterSPs'.
 * nested filter is defined in property config as regular filter.
 *
 * example usage:
 *
 * 10 => array(
 *        'class' => 'perun:ProxyFilter',
 *        'filterSPs' => array('disableSpEntityId01', 'disableSpEntityId02'),
 *        'config' => array(
 *            'class' => 'perun:NestedFilter',
 *            ...
 *        ),
 * )
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class ProxyFilter extends \SimpleSAML\Auth\ProcessingFilter
{

    private $config;
    private $nestedClass;
    private $filterSPs;
    private $reserved;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (!isset($config['config'])) {
            throw new Exception(
                "perun:ProxyFilter: missing mandatory configuration option 'config'"
            );
        }
        if (!isset($config['config']['class'])) {
            throw new Exception(
                "perun:ProxyFilter: missing mandatory configuration option config['class']"
            );
        }
        if (!isset($config['filterSPs'])) {
            throw new Exception(
                "perun:ProxyFilter: missing mandatory configuration option 'filterSPs'."
            );
        }

        $this->nestedClass = (string)$config['config']['class'];
        unset($config['config']['class']);
        $this->config = (array)$config['config'];
        $this->filterSPs = (array)$config['filterSPs'];
        $this->reserved = (array)$reserved;
    }

    public function process(&$request)
    {
        assert('is_array($request)');

        foreach ($this->filterSPs as $sp) {
            $currentSp = $request['Destination']['entityid'];
            if ($sp == $currentSp) {
                Logger::info(
                    "perun.ProxyFilter: Filtering out filter $this->nestedClass for SP $currentSp"
                );

                return;
            }
        }

        list($module, $simpleClass) = explode(":", $this->nestedClass);
        $className = '\SimpleSAML\Module\\' . $module . '\Auth\Process\\' . $simpleClass;
        $authFilter = new $className($this->config, $this->reserved);
        $authFilter->process($request);
    }
}
