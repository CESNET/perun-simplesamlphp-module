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
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
abstract class sspmod_perun_IdpListsService
{
	const CONFIG_FILE_NAME = 'module_perun.php';
	const PROPNAME_IDP_LIST_SERVICE_TYPE = 'idpListServiceType';

	const CSV = 'csv';
	const DB = 'db';

	/**
	 * Function returns the instance of sspmod_perun_IdPListsService by configuration
	 * Default is CSV
	 * @return sspmod_perun_IdpListsServiceCsv|sspmod_perun_IdpListsServiceDB
	 */
	public static function getInstance() {
		$configuration = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		$idpListServiceType = $configuration->getString(self::PROPNAME_IDP_LIST_SERVICE_TYPE, self::CSV);
		if ($idpListServiceType === self::CSV) {
			return new sspmod_perun_IdpListsServiceCsv();
		} else if ($idpListServiceType === self::DB) {
			return new sspmod_perun_IdpListsServiceDB();
		} else {
			throw new SimpleSAML_Error_Exception('Unknown idpListService type. Hint: try ' . self::CSV . ' or ' . self::DB);
		}
	}

	/**
	 * Function returns all whitelisted IdPs as array
	 * @return array of all whitelisted IdPs, every IdP is represents as array
	 */
	public abstract function getWhitelist();

	/**
	 * Function returns all greylisted IdPs as array
	 * @return array of all greylisted IdPs, every IdP is represents as array
	 */
	public abstract function getGreylist();

	/**
	 * Function returns all whitelisted entityIds as array
	 * @return array of all whitelisted entityIds
	 */
	public abstract function getWhitelistEntityIds();

	/**
	 * Function returns all greylisted entityIds as array
	 * @return array of all greylisted entityIds
	 */
	public abstract function getGreylistEntityIds();

	/**
	 * @param string $entityID
	 * @return bool true if whitelist contains given entityID, false otherwise.
	 */
	public abstract function isWhitelisted($entityID);

	/**
	 * @param string $entityID
	 * @return bool true if greylist contains given entityID, false otherwise.
	 */
	public abstract function isGreylisted($entityID);


	/**
	 * Function check if this entity is already whitelisted. If not, it will be added into
	 * whitelist and if this entityId is greylisted, it will be removed from greylist.
	 * @param string $entityID
	 * @param null|string $reason
	 */
	public abstract function whitelistIdp($entityID, $reason = null);

}
