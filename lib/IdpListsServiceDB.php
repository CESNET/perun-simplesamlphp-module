<?php
include 'DatabaseCommand.php';

/**
 * Implementation of sspmod_perun_IdpListsService using DB
 *
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class sspmod_perun_IdpListsServiceDB extends sspmod_perun_IdpListsService
{


	function getWhitelist()
	{
		return DatabaseCommand::getAllIdps(DatabaseCommand::WHITELIST);
	}

	function getGreylist()
	{
		return DatabaseCommand::getAllIdps(DatabaseCommand::GREYLIST);
	}


	function getWhitelistEntityIds()
	{
		return DatabaseCommand::getAllEntityIds(DatabaseCommand::WHITELIST);
	}


	function getGreylistEntityIds()
	{
		return DatabaseCommand::getAllEntityIds(DatabaseCommand::GREYLIST);
	}


	function isWhitelisted($entityID)
	{
		return in_array($entityID, $this->getWhitelistEntityIds());
	}


	function isGreylisted($entityID)
	{
		return in_array($entityID, $this->getGreylistEntityIds());
	}


	function whitelistIdp($entityID, $reason = null)
	{
		if (!$this->isWhitelisted($entityID)) {
			DatabaseCommand::insertTolist(DatabaseCommand::WHITELIST, $entityID, $reason);
			if ($this->isGreylisted($entityID)) {
				DatabaseCommand::deleteFromList(DatabaseCommand::GREYLIST, $entityID);
			}
		}
	}
}