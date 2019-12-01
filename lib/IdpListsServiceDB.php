<?php

namespace SimpleSAML\Module\perun;

/**
 * Implementation of IdpListsService using DB
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class IdpListsServiceDB extends IdpListsService
{

    public function getWhitelist()
    {
        return DatabaseCommand::getAllIdps(DatabaseCommand::WHITELIST);
    }

    public function getGreylist()
    {
        return DatabaseCommand::getAllIdps(DatabaseCommand::GREYLIST);
    }

    public function getWhitelistEntityIds()
    {
        return DatabaseCommand::getAllEntityIds(DatabaseCommand::WHITELIST);
    }

    public function getGreylistEntityIds()
    {
        return DatabaseCommand::getAllEntityIds(DatabaseCommand::GREYLIST);
    }

    public function isWhitelisted($entityID)
    {
        return in_array($entityID, $this->getWhitelistEntityIds());
    }

    public function isGreylisted($entityID)
    {
        return in_array($entityID, $this->getGreylistEntityIds());
    }

    public function whitelistIdp($entityID, $reason = null)
    {
        if (!$this->isWhitelisted($entityID)) {
            DatabaseCommand::insertTolist(DatabaseCommand::WHITELIST, $entityID, $reason);
            if ($this->isGreylisted($entityID)) {
                DatabaseCommand::deleteFromList(DatabaseCommand::GREYLIST, $entityID);
            }
        }
    }
}
