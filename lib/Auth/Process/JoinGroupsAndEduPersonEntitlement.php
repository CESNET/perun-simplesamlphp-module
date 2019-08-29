<?php

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Error\Exception;

/**
 * Class JoinGroupsAndEduPersonEntitlement
 *
 * This filter joins eduPersonEntitlement attribute from perun with groups from PerunGroups filter.
 *
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
class JoinGroupsAndEduPersonEntitlement extends \SimpleSAML\Auth\ProcessingFilter
{
    const EDU_PERSON_ENTITLEMENT = 'eduPersonEntitlement';
    const FORWARDED_EDU_PERSON_ENTITLEMENT = 'forwardedEduPersonEntitlement';

    private $eduPersonEntitlement;
    private $forwardedEduPersonEntitlement;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert('is_array($config)');

        if (!isset($config[self::EDU_PERSON_ENTITLEMENT])) {
            throw new Exception(
                'perun:JoinGroupsAndEduPersonEntitlement: missing mandatory configuration option ' .
                self::EDU_PERSON_ENTITLEMENT . '.'
            );
        }
        $this->eduPersonEntitlement = $config[self::EDU_PERSON_ENTITLEMENT];

        if (!isset($config[self::FORWARDED_EDU_PERSON_ENTITLEMENT])) {
            throw new Exception(
                'perun:JoinGroupsAndEduPersonEntitlement: missing mandatory configuration option ' .
                self::FORWARDED_EDU_PERSON_ENTITLEMENT . '.'
            );
        }
        $this->forwardedEduPersonEntitlement = $config[self::FORWARDED_EDU_PERSON_ENTITLEMENT];
    }

    public function process(&$request)
    {
        if (isset($request['Attributes'][$this->eduPersonEntitlement]) &&
            isset($request['Attributes'][$this->forwardedEduPersonEntitlement])) {
            $request['Attributes'][$this->eduPersonEntitlement] = array_merge(
                $request['Attributes'][$this->eduPersonEntitlement],
                $request['Attributes'][$this->forwardedEduPersonEntitlement]
            );
        } else {
            throw new Exception(
                'perun:JoinGroupsAndEduPersonEntitlement: ' .
                'missing at least one of mandatory fields (\'Attributes.' . $this->eduPersonEntitlement .
                '\' or \'Attributes.' . $this->forwardedEduPersonEntitlement . '\' in request.'
            );
        }

        unset($request['Attributes'][$this->forwardedEduPersonEntitlement]);
    }
}
