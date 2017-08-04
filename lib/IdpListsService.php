<?php

/**
 * This interface provides abstraction of manipulation with lists of IdPs
 * saved and managed by Proxy IdP. e.g. Whitelist or greylist.
 * It should abstract from a form how the data is stored.
 *
 * IdP here is represented by an associative array with keys:
 * 	entityid, timestamp and optionally reason.
 * when the IdP was added or lately modified.
 *
 * Note that implementation should be thread/concurrency safe.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
interface sspmod_perun_IdpListsService
{

	/**
	 * @return array of all latest (by timestamp) whitelisted IdPs.
	 * note that each IdP can be presented only once with the latest timestamp.
	 */
	function getLatestWhitelist();

	/**
	 * @param string $entityID
	 * @return bool true if whitelist contains given entityID, false otherwise.
	 */
	function isWhitelisted($entityID);


	/**
	 * @return array of all latest (by timestamp) greylisted IdPs.
	 * note that each IdP can be presented only once with the latest timestamp.
	 */
	function getLatestGreylist();

	/**
	 * @param string $entityID
	 * @return bool true if greylist contains given entityID, false otherwise.
	 */
	function isGreylisted($entityID);


	/**
	 * Basically do the same as addIdpToWhitelist and removeIdpFromGreylist methods.
	 * Note implementation should take care of transaction.
	 * @param string $entityID
	 * @param null|string $reason
	 */
	function whitelistIdp($entityID, $reason = null);
}
