<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Module\perun\databaseCommand\IdpListsServiceDbCmd;

/**
 * Implementation of IdpListsService using DB
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class IdpListsServiceDB extends IdpListsService
{
    private $idpListServiceDbCmd;

    public function __construct()
    {
        $this->idpListServiceDbCmd = new IdpListsServiceDbCmd();
    }

    public function getWhitelist()
    {
        return $this->idpListServiceDbCmd->getAllIdps($this->idpListServiceDbCmd::WHITELIST);
    }

    public function getGreylist()
    {
        return $this->idpListServiceDbCmd->getAllIdps($this->idpListServiceDbCmd::GREYLIST);
    }

    public function getWhitelistEntityIds()
    {
        return $this->idpListServiceDbCmd->getAllEntityIds($this->idpListServiceDbCmd::WHITELIST);
    }

    public function getGreylistEntityIds()
    {
        return $this->idpListServiceDbCmd->getAllEntityIds($this->idpListServiceDbCmd::GREYLIST);
    }

    public function isWhitelisted($entityID)
    {
        return in_array($entityID, $this->getWhitelistEntityIds(), true);
    }

    public function isGreylisted($entityID)
    {
        return in_array($entityID, $this->getGreylistEntityIds(), true);
    }

    public function whitelistIdp($entityID, $reason = null)
    {
        if (! $this->isWhitelisted($entityID)) {
            $this->idpListServiceDbCmd->insertToList($this->idpListServiceDbCmd::WHITELIST, $entityID, $reason);
            if ($this->isGreylisted($entityID)) {
                $this->idpListServiceDbCmd->deleteFromList($this->idpListServiceDbCmd::GREYLIST, $entityID);
            }
        }
    }
}
