<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Module\perun\Adapter;
use SimpleSAML\Module\perun\PerunConstants;

/**
 * Class tries to find user in Perun using the extLogin and extSourceName (in case of RPC adapter).
 *
 * If the user cannot be found, it redirects user to the registration URL.
 */
class PerunUserGroups extends ProcessingFilter
{
    public const STAGE = 'perun:PerunUserGroups';
    public const DEBUG_PREFIX = self::STAGE . ' - ';

    public const INTERFACE = 'interface';

    private $adapter;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $filterConfig = Configuration::loadFromArray($config);

        $interface = $filterConfig->getString(self::INTERFACE, Adapter::RPC);

        $this->adapter = Adapter::getInstance($interface);
    }

    public function process(&$request)
    {
        assert(is_array($request));
        assert(array_key_exists(PerunConstants::USER, $request));
        $user = $request[PerunConstants::PERUN][PerunConstants::USER] ?? null;
        if (empty($user)) {
            throw new Exception(
                self::DEBUG_PREFIX . 'Cannot find Perun user in request. Did you properly configure ' . PerunUser::STAGE . ' filter before this filter in the processing chain?'
            );
        }

        $spEntityId = $request[PerunConstants::SP_METADATA][PerunConstants::SP_METADATA_ENTITYID] ?? null;
        if (empty($spEntityId)) {
            Logger::debug(self::DEBUG_PREFIX . 'No SP EntityID available, user groups will be empty');
            throw new Exception(self::DEBUG_PREFIX . 'Cannot find SP EntityID');
        }

        $groups = $this->adapter->getUsersGroupsOnFacility($spEntityId, $user->getId());
        $request[PerunConstants::PERUN][PerunConstants::USER_GROUPS] = $groups;
    }
}
