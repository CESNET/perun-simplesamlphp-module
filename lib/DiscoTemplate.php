<?php


/**
 * This class extends basic SimpleSAML template class. It provides some utils functions used in templates
 * specific for Discovery services so template do not have to access directly $this->data field.
 *
 * Here should NOT be defined any view specific methods.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_DiscoTemplate extends SimpleSAML_XHTML_Template
{

	/**
	 * sspmod_perun_DiscoTemplate constructor.
	 * @param SimpleSAML_Configuration $configuration of SimpleSAMLphp
	 */
	function __construct(SimpleSAML_Configuration $configuration) {
		parent::__construct($configuration, 'perun:disco-tpl.php', 'disco');

		// Translate title in header
		$this->data['header'] = $this->t(isset($this->data['header']) ? $this->data['header'] : 'selectidp' );
	}


	/**
	 * @return array metadata of preferred IdP if exists or null if not
	 */
	public function getPreferredIdp() {
		if (isset($this->data['preferredidp']) && !empty($this->data['preferredidp'])) {
			return $this->getAllIdps()[$this->data['preferredidp']];
		}
		return null;
	}


	/**
	 * @param string $tag desired tag. If not provided 'misc' is used for all untagged idps.
	 * @return array list of idp metadatas from declared tag or untagged (misc) idps are returned.
	 */
	public function getIdps($tag = 'misc') {
		return $this->data['idplist'][$tag];
	}


	/**
	 * @return array structure of idp metadatas divided by tags.
	 * example structure:
	 *
	 * array(
	 * 		'social' => array(
	 * 			array( ...metadata1... ),
	 * 			array( ...metadata2... ),
	 * 		)
	 * 		// misc represents untagged idps
	 * 		'misc' => array(
	 * 			array( ...metadata2... ),
	 * 			array( ...metadata3... ),
	 * 		)
	 * )
	 *
	 * note: one idp can be placed in more tags
	 *
	 */
	public function getTaggedIdps() {
		return $this->data['idplist'];
	}


	/**
	 * @return array list of all idp metadatas ignoring tagging
	 */
	public function getAllIdps() {
		$allIdps = array();
		foreach ($this->data['idplist'] AS $tag => $idplist) {
			$allIdps = array_merge($idplist, $allIdps);
		}
		return $allIdps;
	}


	/**
	 * @return bool true if SP has property 'disco.doNotFilterIdps' set to true in its metadata. False otherwise.
	 */
	public function isOriginalSpNonFilteringIdPs() {
		return (isset($this->data['originalsp']['disco.doNotFilterIdps']) && $this->data['originalsp']['disco.doNotFilterIdps'] === TRUE);
	}


	/**
	 * @param string $idpEntityId
	 * @return string url where user should be redirected when he choose idp
	 */
	public function getContinueUrl($idpEntityId) {
		return sspmod_perun_Disco::buildContinueUrl($this->data['entityID'], $this->data['return'], $this->data['returnIDParam'], $idpEntityId);
	}


	/**
	 * @param array $metadata
	 * @return string translated name of idp or sp based on its metadata information
	 */
	public function getTranslatedEntityName($metadata) {
		if (isset($metadata['UIInfo']['DisplayName'])) {
			$displayName = $metadata['UIInfo']['DisplayName'];
			assert('is_array($displayName)'); // Should always be an array of language code -> translation
			if (!empty($displayName)) {
				return $this->getTranslation($displayName);
			}
		}

		if (array_key_exists('name', $metadata)) {
			if (is_array($metadata['name'])) {
				return $this->getTranslation($metadata['name']);
			} else {
				return $metadata['name'];
			}
		}
		return $metadata['entityid'];
	}



}
