<?php

/**
 * Filter checks whether UID attribute is object of type DOMNodeList.
 * If yes, then it supposes it is derived form XML
 * <saml2:NameID NameQualifier="https://idp" SPNameQualifier="https://sp">uid</saml2:NameID>
 * which converts to [NameQualifier]![SPNameQualifier]![TextValue] resp. https://idp!https://sp!uid
 * If configuration option targetAttribute is provided, uid attribute stays unchanged and new attribute is filled.
 * If no, uid attribute is overwritten.
 *
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

class sspmod_perun_Auth_Process_StringifyTargetedID extends SimpleSAML_Auth_ProcessingFilter
{
	private $uidAttr;
	private $targetAttr;

	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		if (!isset($config['uidAttr'])) {
			throw new SimpleSAML_Error_Exception("perun:ProcessTargetedID: missing mandatory configuration option 'uidAttr'.");
		}
		if (!isset($config['targetAttr'])) {
			$config['targetAttr'] = $config['uidAttr'];
		}

		$this->uidAttr = (string) $config['uidAttr'];
		$this->targetAttr = (string) $config['targetAttr'];
	}

	public function process(&$request)
	{
		assert('is_array($request)');

		if (!empty($request['Attributes'][$this->uidAttr]))
		{
			$stringified = $this->stringify($request['Attributes'][$this->uidAttr][0]);
			$request['Attributes'][$this->targetAttr] = array($stringified);
		}

	}

	/**
	 * Convert NameID value into the text representation.
	 */
	private function stringify($attributeValue) {
		if (is_object($attributeValue) && get_class($attributeValue) == "DOMNodeList") {

			$nameid = new SAML2_XML_saml_NameID($attributeValue->item(0));

			return $nameid->NameQualifier . '!' . $nameid->SPNameQualifier . '!' . $nameid->value;
		} else {
			return $attributeValue;
		}
	}

}
