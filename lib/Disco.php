<?php


/**
 * This class implements a IdP discovery service.
 *
 * This module extends the DiscoPower IdP disco handler, so it needs to be avaliable and enabled and configured.
 *
 * It adds functionality of whitelisting and greylisting IdPs.
 * for security reasons for blacklisting please manipulate directly with metadata. In case of manual idps
 * comment them out or in case of automated metadata fetching configure blacklist in config-metarefresh.php
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class sspmod_perun_Disco extends sspmod_discopower_PowerIdPDisco
{
	const CONFIG_FILE_NAME = 'module_perun.php';
	const PROPNAME_DISABLE_WHITELISTING = 'disco.disableWhitelisting';
	const PROPNAME_PREFIX = "disco.removeAuthnContextClassRefPrefix";

	private $originalsp;
	private $whitelist;
	private $greylist;
	private $service;
	private $authnContextClassRef = array();

	public function __construct(array $metadataSets, $instance)
	{
		if (!array_key_exists('return', $_GET)) {
			throw new Exception('Missing parameter: return');
		} else {
			$returnURL = \SimpleSAML\Utils\HTTP::checkURLAllowed($_GET['return']);
		}
		parse_str(parse_url($returnURL)['query'], $query);
		$id = explode(":", $query['AuthID'])[0];
		$state = SimpleSAML_Auth_State::loadState($id, 'saml:sp:sso', true);

		if (isset($state['saml:RequestedAuthnContext']['AuthnContextClassRef'])) {
			$this->authnContextClassRef = $state['saml:RequestedAuthnContext']['AuthnContextClassRef'];
			$this->removeAuthContextClassRefWithPrefix($state);
		}

		$id = SimpleSAML_Auth_State::saveState($state, 'saml:sp:sso');

		$e = explode("=", $returnURL)[0];
		$newReturnURL = $e . "=" . urlencode($id);
		$_GET['return'] = $newReturnURL;

		parent::__construct($metadataSets, $instance);

		$this->originalsp = $state['SPMetadata'];
		$this->service = sspmod_perun_IdpListsService::getInstance();
		$this->whitelist = $this->service->getWhitelistEntityIds();
		$this->greylist = $this->service->getGreylistEntityIds();
	}


	/**
	 * Handles a request to this discovery service. It is enry point of Discovery service.
	 *
	 * The IdP disco parameters should be set before calling this function.
	 */
	public function handleRequest()
	{
		// test if user has selected an idp or idp can be deremine automatically somehow.
		$this->start();

		// no choice possible. Show discovery service page
		$idpList = $this->getIdPList();
		if (isset($this->originalsp['disco.addInstitutionApp']) && $this->originalsp['disco.addInstitutionApp'] === true ) {
			$idpList = $this->filterAddInstitutionList($idpList);
		} else {
			$idpList = $this->filterList($idpList);
		}
		$preferredIdP = $this->getRecommendedIdP();
		$preferredIdP = array_key_exists($preferredIdP, $idpList) ? $preferredIdP : null;

		if (sizeof($idpList) === 1) {
			$idp = array_keys($idpList)[0];
			$url = sspmod_perun_Disco::buildContinueUrl($this->spEntityId, $this->returnURL, $this->returnIdParam, $idp);
			SimpleSAML\Logger::info('perun.Disco: Only one Idp left. Redirecting automatically. IdP: ' . $idp);
			SimpleSAML\Utils\HTTP::redirectTrustedURL($url);
		}

		$t = new sspmod_perun_DiscoTemplate($this->config);
		$t->data['originalsp'] = $this->originalsp;
		$t->data['idplist'] = $this->idplistStructured($idpList);
		$t->data['preferredidp'] = $preferredIdP;
		$t->data['entityID'] = $this->spEntityId;
		$t->data['return'] = $this->returnURL;
		$t->data['returnIDParam'] = $this->returnIdParam;
		$t->data['AuthnContextClassRef'] = $this->authnContextClassRef;
		$t->show();
	}


	/**
	 * Filter a list of entities according to any filters defined in the parent class, plus
	 *
	 * @param array $list A map of entities to filter.
	 * @return array The list in $list after filtering entities.
	 * @throws SimpleSAML_Error_Exception if all IdPs are filtered out and no one left.
	 */
	protected function filterList($list)
	{
		$conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		$disableWhitelisting = $conf->getBoolean(self::PROPNAME_DISABLE_WHITELISTING, false);

		if (!isset($this->originalsp['disco.doNotFilterIdps']) || !$this->originalsp['disco.doNotFilterIdps']) {

			$list = parent::filterList($list);
			$list = $this->scoping($list);
			if (!$disableWhitelisting) {
				$list = $this->whitelisting($list);
			}
			$list = $this->greylisting($list);
			$list = $this->greylistingPerSP($list, $this->originalsp);
		}

		if (empty($list)) {
			throw new SimpleSAML_Error_Exception('All IdPs has been filtered out. And no one left.');
		}

		return $list;
	}

	/**
	 * Filter a list of entities for addInstitution app according to if entityID is whitelisted or not
	 *
	 * @param array $list A map of entities to filter.
	 * @return array The list in $list after filtering entities.
	 * @throws SimpleSAML_Error_Exception if all IdPs are filtered out and no one left.
	 */
	protected function filterAddInstitutionList($list)
	{
		foreach ($list as $entityId => $idp) {
			if (in_array($entityId, $this->whitelist)){
				unset($list[$entityId]);
			}
		}

		if (empty($list)) {
			throw new SimpleSAML_Error_Exception('All IdPs has been filtered out. And no one left.');
		}

		return $list;

	}

	/**
	 * Filter out IdP which are not in SAML2 Scoping attribute list (SAML2 feature)
	 * @param $list
	 * @return array of idps
	 */
	protected function scoping($list)
	{
		if (!empty($this->scopedIDPList)) {
			foreach ($list as $entityId => $idp) {
				if (!in_array($entityId, $this->scopedIDPList)) {
					unset($list[$entityId]);
				}
			}
		}
		//SimpleSAML\Logger::debug('perun.Disco.filterList: Idps after SAML2 Scoping: ' . var_export(array_keys($list), true));
		return $list;
	}


	protected function whitelisting($list)
	{
		foreach ($list as $entityId => $idp) {
			$unset = true;

			if (in_array($entityId, $this->whitelist)){
				$unset = false;
			}
			if (isset($idp['EntityAttributes']['http://macedir.org/entity-category-support'])) {
				$entityCategorySupport = $idp['EntityAttributes']['http://macedir.org/entity-category-support'];
				if (in_array("http://refeds.org/category/research-and-scholarship", $entityCategorySupport)) {
					$unset = false;
				}
				if (in_array("http://www.geant.net/uri/dataprotection-code-of-conduct/v1", $entityCategorySupport)) {
					$unset = false;
				}
			}
			if (isset($idp['CoCo']) and $idp['CoCo'] === true) {
				$unset = false;
			}
			if (isset($idp['RaS']) and $idp['RaS'] === true) {
				$unset = false;
			}


			if ($unset === true) {
				unset($list[$entityId]);
			}
		}
		//SimpleSAML\Logger::debug('perun.Disco.filterList: Idps after Whitelisting: ' . var_export(array_keys($list), true));
		return $list;
	}


	protected function greylisting($list)
	{
		foreach ($list as $entityId => $idp) {
			if (in_array($entityId, $this->greylist)) {
				unset($list[$entityId]);
			}
		}

		//SimpleSAML\Logger::debug('perun.Disco.filterList: Idps after Greylisting: ' . var_export(array_keys($list), true));
		return $list;
	}


	protected function greylistingPerSP($list, $sp)
	{
		foreach ($list as $entityId => $idp) {
			if (isset($sp['greylist']) && in_array($entityId, $sp['greylist'])) {
				unset($list[$entityId]);
			}
		}

		//SimpleSAML\Logger::debug('perun.Disco.filterList: Idps after Greylisting per SP: ' . var_export(array_keys($list), true));
		return $list;
	}


	/**
	 * @param $entityID
	 * @param $return
	 * @param $returnIDParam
	 * @param $idpEntityId
	 * @return string url where user should be redirected when he choose idp
	 */
	public static function buildContinueUrl($entityID, $return, $returnIDParam, $idpEntityId) {
		$url = '?' .
			'entityID=' . urlencode($entityID) . '&' .
			'return=' . urlencode($return) . '&' .
			'returnIDParam=' . urlencode($returnIDParam) . '&' .
			'idpentityid=' . urlencode($idpEntityId);

		return $url;
	}

	/**
	 * @param $entityID
	 * @param $return
	 * @param $returnIDParam
	 * @return string url where user should be redirected when he choose idp
	 */
	public static function buildContinueUrlWithoutIdPEntityId($entityID, $return, $returnIDParam) {
		$url = '?' .
			'entityID=' . urlencode($entityID) . '&' .
			'return=' . urlencode($return) . '&' .
			'returnIDParam=' . urlencode($returnIDParam);

		return $url;
	}

	/**
	 * This method remove all AuthnContextClassRef which start with prefix from configuration
	 * @param $state
	 */
	public function removeAuthContextClassRefWithPrefix(&$state) {
		$conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		$prefix = $conf->getString(self::PROPNAME_PREFIX, null);

		if (is_null($prefix)) {
			return;
		}
		unset($state['saml:RequestedAuthnContext']['AuthnContextClassRef']);
		$array = array();
		foreach ($this->authnContextClassRef as $value) {
			if (!(substr($value, 0, strlen($prefix)) === $prefix)) {
				array_push($array, $value);
			}
		}
		if (!empty($array)) {
			$state['saml:RequestedAuthnContext']['AuthnContextClassRef'] = $array;
		}
	}

}
