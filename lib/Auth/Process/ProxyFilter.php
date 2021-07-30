<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * Class sspmod_perun_Auth_Process_ProxyFilter
 *
 * This filter allows to disable/enable nested filters for particular SP or for users with one of (black/white)listed
 * attribute values. Based on the mode of operation, the nested filters ARE (whitelist) or ARE NOT (blacklist) run when
 * any of the attribute values matches. SPs are defined by theirs entityID in property 'filterSPs'. User attributes are
 * defined as a map 'attrName'=>['value1','value2'] in property 'filterAttributes'. Nested filters are defined in the
 * authproc property in the same format as in config. If only one filter is needed, it can be specified in the config
 * property.
 *
 * example usage:
 *
 * 10 => [ 'class' => 'perun:ProxyFilter', 'filterSPs' => ['disableSpEntityId01', 'disableSpEntityId02'],
 * 'filterAttributes' => [ 'eduPersonPrincipalName' => ['test@example.com'], 'eduPersonAffiliation' =>
 * ['affiliate','member'], ], 'config' => [ 'class' => 'perun:NestedFilter', // ... ], ], 20 => [ 'class' =>
 * 'perun:ProxyFilter', 'mode' => 'whitelist', 'filterSPs' => ['enableSpEntityId01', 'enableSpEntityId02'], 'authproc'
 * => [ [ 'class' => 'perun:NestedFilter1', // ... ], [ 'class' => 'perun:NestedFilter2', // ... ], ], ],
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class ProxyFilter extends \SimpleSAML\Auth\ProcessingFilter
{
    public const MODE_BLACKLIST = 'blacklist';

    public const MODE_WHITELIST = 'whitelist';

    public const MODES = [self::MODE_BLACKLIST, self::MODE_WHITELIST];

    private $authproc;

    private $nestedClasses;

    private $filterSPs;

    private $filterAttributes;

    private $mode;

    private $reserved;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        $conf = Configuration::loadFromArray($config);
        $this->filterSPs = $conf->getArray('filterSPs', []);
        $this->filterAttributes = $conf->getArray('filterAttributes', []);
        $this->mode = $conf->getValueValidate('mode', self::MODES, self::MODE_BLACKLIST);

        $this->authproc = $conf->getArray('authproc', []);
        $this->authproc[] = $conf->getArray('config', []);
        $this->authproc = array_filter($this->authproc);
        $this->nestedClasses = implode(',', array_map(
            function ($config) {
                return is_string($config) ? $config : $config['class'];
            },
            $this->authproc
        ));

        $this->reserved = (array) $reserved;
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
            $this->processState($request);
        } elseif ($this->mode === self::MODE_WHITELIST) {
            Logger::info(
                sprintf(
                    'perun.ProxyFilter: Not running filter %s for SP %s',
                    $this->nestedClasses,
                    $request['Destination']['entityid']
                )
            );
        }
    }

    private function shouldRunForSP($currentSp, $default)
    {
        foreach ($this->filterSPs as $sp) {
            if ($sp === $currentSp) {
                $shouldRun = ! $default;
                Logger::info(
                    sprintf(
                        'perun.ProxyFilter: %s filter %s for SP %s',
                        $shouldRun ? 'Running' : 'Filtering out',
                        $this->nestedClasses,
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
                    if (in_array($value, $attributes[$attr], true)) {
                        $shouldRun = ! $default;
                        Logger::info(
                            sprintf(
                                'perun.ProxyFilter: %s filter %s because %s contains %s',
                                $shouldRun ? 'Running' : 'Filtering out',
                                $this->nestedClasses,
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

    /**
     * Parse an array of authentication processing filters.
     *
     * @see https://github.com/simplesamlphp/simplesamlphp/blob/simplesamlphp-1.17/lib/SimpleSAML/Auth/ProcessingChain.php
     *
     * @param array $filterSrc  Array with filter configuration.
     * @return array  Array of ProcessingFilter objects.
     */
    private static function parseFilterList($filterSrc)
    {
        assert(is_array($filterSrc));

        $parsedFilters = [];

        foreach ($filterSrc as $priority => $filter) {
            if (is_string($filter)) {
                $filter = [
                    'class' => $filter,
                ];
            }

            if (! is_array($filter)) {
                throw new \Exception('Invalid authentication processing filter configuration: ' .
                    'One of the filters wasn\'t a string or an array.');
            }

            $parsedFilters[] = self::parseFilter($filter, $priority);
        }

        return $parsedFilters;
    }

    /**
     * Parse an authentication processing filter.
     *
     * @see https://github.com/simplesamlphp/simplesamlphp/blob/simplesamlphp-1.17/lib/SimpleSAML/Auth/ProcessingChain.php
     *
     * @param array $config      Array with the authentication processing filter configuration.
     * @param int $priority      The priority of the current filter, (not included in the filter
     * definition.)
     * @return ProcessingFilter  The parsed filter.
     */
    private static function parseFilter($config, $priority)
    {
        assert(is_array($config));

        if (! array_key_exists('class', $config)) {
            throw new \Exception('Authentication processing filter without name given.');
        }

        $className = \SimpleSAML\Module::resolveClass(
            $config['class'],
            'Auth\Process',
            '\SimpleSAML\Auth\ProcessingFilter'
        );
        $config['%priority'] = $priority;
        unset($config['class']);
        return new $className($config, null);
    }

    /**
     * Process the given state.
     *
     * @see https://github.com/simplesamlphp/simplesamlphp/blob/simplesamlphp-1.17/lib/SimpleSAML/Auth/ProcessingChain.php
     *
     * This function will only return if processing completes. If processing requires showing
     * a page to the user, we will not be able to return from this function. There are two ways
     * this can be handled:
     * - Redirect to a URL: We will redirect to the URL set in $state['ReturnURL'].
     * - Call a function: We will call the function set in $state['ReturnCall'].
     *
     * If an exception is thrown during processing, it should be handled by the caller of
     * this function. If the user has redirected to a different page, the exception will be
     * returned through the exception handler defined on the state array. See
     * State for more information.
     *
     * @see State
     * @see State::EXCEPTION_HANDLER_URL
     * @see State::EXCEPTION_HANDLER_FUNC
     *
     * @param array $state  The state we are processing.
     */
    private function processState(&$state)
    {
        $filters = self::parseFilterList($this->authproc);
        try {
            while (count($filters) > 0) {
                $filter = array_shift($filters);
                $filter->process($state);
            }
        } catch (\SimpleSAML\Error\Exception $e) {
            // No need to convert the exception
            throw $e;
        } catch (\Exception $e) {
            /*
             * To be consistent with the exception we return after an redirect,
             * we convert this exception before returning it.
             */
            throw new \SimpleSAML\Error\UnserializableException($e);
        }

        // Completed
    }
}
