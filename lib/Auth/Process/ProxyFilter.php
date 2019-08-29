<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Configuration;

/**
 * Class sspmod_perun_Auth_Process_ProxyFilter
 *
 * This filter allows to disable nested filter for particular SP
 * or for users with one of (black)listed attribute values.
 * When any of the values matches, the nested filter is NOT run.
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
 * ]
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class ProxyFilter extends \SimpleSAML\Auth\ProcessingFilter
{

    private $config;
    private $nestedClass;
    private $filterSPs;
    private $filterAttributes;
    private $reserved;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        $conf = Configuration::loadFromArray($config);
        $this->config = $conf->getArray('config');
        $this->nestedClass = Configuration::loadFromArray($this->config)->getString('class');
        unset($this->config['class']);
        $this->filterSPs = $conf->getArray('filterSPs', []);
        $this->filterAttributes = $conf->getArray('filterAttributes', []);

        $this->reserved = (array)$reserved;
    }

    public function process(&$request)
    {
        assert('is_array($request)');

        foreach ($this->filterAttributes as $attr => $values) {
            if (!isset($request['Attributes'][$attr]) || !is_array($request['Attributes'][$attr])) {
                continue;
            }
            foreach ($values as $value) {
                if (in_array($value, $request['Attributes'][$attr])) {
                    Logger::info(
                        sprintf(
                            'perun.ProxyFilter: Filtering out filter %s because %s contains %s',
                            $this->nestedClass,
                            $attr,
                            $value
                        )
                    );

                    return;
                }
            }
        }

        foreach ($this->filterSPs as $sp) {
            $currentSp = $request['Destination']['entityid'];
            if ($sp === $currentSp) {
                Logger::info(
                    sprintf(
                        'perun.ProxyFilter: Filtering out filter %s for SP %s',
                        $this->nestedClass,
                        $currentSp
                    )
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
