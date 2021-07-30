<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * Class EntitlementUtils
 *
 * This class contains common functions of PerunEntitlement and PerunEntitlementExtended.
 *
 * @author Dominik Baránek <baranek@ics.muni.cz>
 */
class EntitlementUtils
{
    public const GROUP = 'group';

    public const GROUP_ATTRIBUTES = 'groupAttributes';

    public const DISPLAY_NAME = 'displayName';

    public static function getForwardedEduPersonEntitlement(&$request, $adapter, $forwardedEduPersonEntitlement)
    {
        $result = [];

        if (! isset($request['perun']['user'])) {
            Logger::debug(
                'perun:EntitlementUtils: Object Perun User is not specified.' .
                '=> Skipping getting forwardedEntitlement.'
            );
            return $result;
        }

        $user = $request['perun']['user'];

        try {
            $forwardedEduPersonEntitlementMap = $adapter->getUserAttributesValues(
                $user,
                [$forwardedEduPersonEntitlement]
            );
        } catch (Exception $exception) {
            Logger::error(
                'perun:EntitlementUtils: Exception ' . $exception->getMessage() .
                ' was thrown in method \'getForwardedEduPersonEntitlement\'.'
            );
        }

        if (! empty($forwardedEduPersonEntitlementMap)) {
            $result = array_values($forwardedEduPersonEntitlementMap)[0];
        }

        return $result;
    }

    public static function getCapabilities(&$request, $adapter, $prefix, $authority, $spEntityId)
    {
        $resourceCapabilities = [];
        $facilityCapabilities = [];
        $capabilitiesResult = [];

        try {
            $resourceCapabilities = $adapter->getResourceCapabilities($spEntityId, $request['perun']['groups']);
            $facilityCapabilities = $adapter->getFacilityCapabilities($spEntityId);
        } catch (Exception $exception) {
            Logger::error(
                'perun:EntitlementUtils: Exception ' . $exception->getMessage() .
                ' was thrown in method \'getCapabilities\'.'
            );
        }

        $capabilities = array_unique(array_merge($resourceCapabilities, $facilityCapabilities));

        foreach ($capabilities as $capability) {
            $wrappedCapability = self::capabilitiesWrapper($capability, $prefix, $authority);
            array_push($capabilitiesResult, $wrappedCapability);
        }

        return $capabilitiesResult;
    }

    public static function encodeName($name)
    {
        $charsToSkip = [
            '-' => '%2D',
            '_' => '%5F',
            '.' => '%2E',
            '~' => '%7E',
            '!' => '%21',
            '\'' => '%27',
            '(' => '%28',
            ')' => '%29',
            '*' => '%2A',
        ];

        $name = rawurlencode($name);
        $name = str_replace(array_values($charsToSkip), array_keys($charsToSkip), $name);

        return $name;
    }

    public static function encodeEntitlement($name)
    {
        $charsToSkip = [
            '!' => '%21',
            '$' => '%24',
            '\'' => '%27',
            '(' => '%28',
            ')' => '%29',
            '*' => '%2A',
            ',' => '%2C',
            ';' => '%3B',
            '&' => '%26',
            '=' => '%3D',
            '@' => '%40',
            ':' => '%3A',
            '+' => '%2B',
        ];

        $name = array_map('rawurlencode', explode(':', $name));
        $name = str_replace(array_values($charsToSkip), array_keys($charsToSkip), $name);

        return $name;
    }

    public static function capabilitiesWrapper($capabilities, $prefix, $authority)
    {
        return $prefix . implode(':', self::encodeEntitlement($capabilities)) .
            '#' . $authority;
    }

    public static function groupEntitlementWrapper($uuid, $prefix, $authority)
    {
        return $prefix . self::GROUP . ':' .
            self::encodeName($uuid) . '#' . $authority;
    }

    public static function groupEntitlementWithAttributesWrapper($uuid, $groupName, $prefix, $authority)
    {
        return $prefix . self::GROUP_ATTRIBUTES . ':' . $uuid . '?=' . self::DISPLAY_NAME . '=' .
            self::encodeName($groupName) . '#' . $authority;
    }

    public static function getSpEntityId(&$request)
    {
        if (isset($request['SPMetadata']['entityid'])) {
            return $request['SPMetadata']['entityid'];
        }
        throw new Exception('perun:EntitlementUtils: Cannot find entityID of remote SP. ' .
                'hint: Do you have this filter in IdP context?');
    }
}
