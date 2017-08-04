<?php

/**
 * Class sspmod_perun_Auth_Process_PerunGroups
 *
 * This filter extracts group names from cached groups from PerunIdentity filter and save them into attribute.
 * It means it strongly relays on it.
 * It also translates (renames) given name of group based on associative array 'groupMapping' in SP metadata.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Auth_Process_PerunGroups extends SimpleSAML_Auth_ProcessingFilter
{

	private $attrName;

	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		if (!isset($config['attrName'])) {
			throw new SimpleSAML_Error_Exception("perun:PerunGroups: missing mandatory configuration option 'attrName'.");
		}
		$this->attrName = (string) $config['attrName'];
	}


	public function process(&$request)
	{
		if (isset($request['perun']['groups'])) {
			/** allow IDE hint whisperer
			 * @var sspmod_perun_model_Group[] $groups
			 */
			$groups = $request['perun']['groups'];
		} else {
			throw new SimpleSAML_Error_Exception("perun:PerunGroups: " .
				"missing mandatory field 'perun.groups' in request." .
				"Hint: Did you configured PerunIdentity filter before this filter?"
			);
		}

		$request['Attributes'][$this->attrName] = array();
		foreach ($groups as $group) {
			$groupName = $this->mapGroupName($request, $group->getName());
			array_push($request['Attributes'][$this->attrName], $groupName);
		}
	}

	/**
	 * This method translates given name of group based on associative array 'groupMapping' in SP metadata.
	 * @param $request
	 * @param string $groupName
	 * @return string translated group name
	 */
	protected function mapGroupName($request, $groupName) {
		if (isset($request["SPMetadata"]["groupMapping"]) && isset($request["SPMetadata"]["groupMapping"][$groupName])) {
			SimpleSAML_Logger::debug("Mapping $groupName to " . $request["SPMetadata"]["groupMapping"][$groupName] . " for SP " . $request["SPMetadata"]["entityid"]);
			return $request["SPMetadata"]["groupMapping"][$groupName];
		} else {
			# No mapping defined
			SimpleSAML_Logger::debug("No mapping found for group $groupName for SP " . $request["SPMetadata"]["entityid"]);
			return $groupName;
		}
	}

}
