<?php

/**
 * Implementation of sspmod_perun_IdpListsService using in simple csv files.
 * first column is timestamp, second entityid and third reason
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class sspmod_perun_IdpListsServiceCsv implements sspmod_perun_IdpListsService
{
	private $whitelistFile;
	private $greylistFile;

	/**
	 * sspmod_perun_IdpListsServiceCsv constructor.
	 */
	public function __construct()
	{
		$dir = SimpleSAML\Utils\Config::getConfigDir();
		$this->whitelistFile = $dir.DIRECTORY_SEPARATOR.'idplists'.DIRECTORY_SEPARATOR.'whitelist.csv';
		$this->greylistFile = $dir.DIRECTORY_SEPARATOR.'idplists'.DIRECTORY_SEPARATOR.'greylist.csv';
	}


	function getLatestWhitelist()
	{
		if (!file_exists($this->whitelistFile)) {
			return array();
		}

		$f = fopen($this->whitelistFile, 'r');
		if (flock($f, LOCK_SH)) {

			$latest = array();
			while (($idp = $this->arrayToIdp(fgetcsv($f))) !== false) {
				if (!isset($latest[$idp['entityid']])) {
					$latest[$idp['entityid']] = $idp;
				} else {
					if ($idp['timestamp'] > $latest[$idp['entityid']]['timestamp']) {
						$latest[$idp['entityid']] = $idp;
					}
				}
			}

			fflush($f);
			flock($f, LOCK_UN);
		} else {
			throw new SimpleSAML_Error_Exception("IdpListsServiceCsv - unable to get file lock. Hint: Try to create folder config/idplists and add write rights.");
		}
		fclose($f);

		return array_values($latest);
	}

	function isWhitelisted($entityID)
	{
		if (!file_exists($this->whitelistFile)) {
			return false;
		}

		$result = false;
		$f = fopen($this->whitelistFile, 'r');
		if (flock($f, LOCK_SH)) {

			while (($idp = $this->arrayToIdp(fgetcsv($f))) !== false) {
				if ($idp['entityid'] === $entityID) {
					$result = true;
					break;
				}
			}

			fflush($f);
			flock($f, LOCK_UN);
		} else {
			throw new SimpleSAML_Error_Exception("IdpListsServiceCsv - unable to get file lock. Hint: Try to create folder config/idplists and add write rights.");
		}
		fclose($f);
		return $result;
	}

	function getLatestGreylist()
	{
		if (!file_exists($this->greylistFile)) {
			return array();
		}

		$f = fopen($this->greylistFile, 'r');
		if (flock($f, LOCK_SH)) {

			$latest = array();
			while (($idp = $this->arrayToIdp(fgetcsv($f))) !== false) {
				if (isset($latest[$idp['entityid']])) {
					$latest[$idp['entityid']] = $idp;
				} else {
					if ($idp['timestamp'] > $latest[$idp['entityid']]['timestamp']) {
						$latest[$idp['entityid']] = $idp;
					}
				}
			}

			fflush($f);
			flock($f, LOCK_UN);
		} else {
			throw new SimpleSAML_Error_Exception("IdpListsServiceCsv - unable to get file lock. Hint: Try to create folder config/idplists and add write rights.");
		}
		fclose($f);

		return array_values($latest);
	}

	function isGreylisted($entityID)
	{
		if (!file_exists($this->greylistFile)) {
			return false;
		}

		$result = false;
		$f = fopen($this->greylistFile, 'r');
		if (flock($f, LOCK_SH)) {

			while (($idp = $this->arrayToIdp(fgetcsv($f))) !== false) {
				if ($idp['entityid'] === $entityID) {
					$result = true;
					break;
				}
			}

			fflush($f);
			flock($f, LOCK_UN);
		} else {
			throw new SimpleSAML_Error_Exception("IdpListsServiceCsv - unable to get file lock. Hint: Try to create folder config/idplists and add write rights.");
		}
		fclose($f);
		return $result;
	}

	function whitelistIdp($entityID, $reason = null)
	{
		$wf = fopen($this->whitelistFile, 'a');
		if (flock($wf, LOCK_EX)) {
			$gf = fopen($this->greylistFile, 'c+');
			if (flock($gf, LOCK_EX)) {

				$idp = array(date('Y-m-d H:i:s'), $entityID, $reason);
				fputcsv($wf, $idp);

				$greylist = array();
				while (($idp = $this->arrayToIdp(fgetcsv($gf))) !== false) {
					if ($idp['entityid'] !== $entityID) {
						$greylist[] = $idp;
					}
				}

				ftruncate($gf, 0);
				rewind($gf);

				foreach ($greylist as $idp) {
					fputcsv($gf, array_values($idp));
				}

				fflush($wf);
				fflush($gf);
				// TODO: Possible deadlock? Is this correct order of unlocking?
				flock($gf, LOCK_UN);
				flock($wf, LOCK_UN);
			} else {
				throw new SimpleSAML_Error_Exception("IdpListsServiceCsv - unable to get file lock. Hint: Try to create folder config/idplists and add write rights.");
			}
		} else {
			throw new SimpleSAML_Error_Exception("IdpListsServiceCsv - unable to get file lock. Hint: Try to create folder config/idplists and add write rights.");
		}
		fclose($wf);
		fclose($gf);

	}

	function listToArray($listName){
		if ($listName === "whitelist"){
			$list = $this->whitelistFile;
		} else{
			$list = $this->greylistFile;
		}

		$resultList = array();

		if (!file_exists($list)) {
			return $resultList;
		}

		$f = fopen($list, 'r');
		if (flock($f, LOCK_SH)) {

			while (($idp = $this->arrayToIdp(fgetcsv($f))) !== false) {
				array_push($resultList, $idp['entityid']);
			}

			fflush($f);
			flock($f, LOCK_UN);
		} else {
			throw new SimpleSAML_Error_Exception("IdpListsServiceCsv - unable to get file lock. Hint: Try to create folder config/idplists and add write rights.");
		}
		fclose($f);

		return $resultList;
	}

	private function arrayToIdp($csv) {
		if (!is_array($csv)) return false;

		$idp = array();
		$idp['timestamp'] = $csv[0];
		$idp['entityid'] = $csv[1];
		$idp['reason'] = $csv[2];
		return $idp;
	}

}
