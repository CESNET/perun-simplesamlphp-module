<?php

/**
 * Class sspmod_perun_Auth_Process_PerunGroups
 *
 * This filter extracts group names from cached groups from PerunIdentity filter and save them into attribute defined by attrName. 
 * By default attribute value will be filled with the groupNamePrefix + groupName.
 *
 * If groupNameAARC is enabled for SP or it is enabled globaly in perun_module.conf, then use groupNamePrefix
 * and groupNameAuthority to construct group name according to the
 * https://aarc-project.eu/wp-content/uploads/2017/11/AARC-JRA1.4A-201710.pdf
 *
 * It is also capable of translation of (renames) group names using 'groupMapping' attribute in SP metadata.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Michal Prochazka <michalp@ics.muni.cz>
 */
class sspmod_perun_Auth_Process_PerunGroups extends SimpleSAML_Auth_ProcessingFilter
{

	const CONFIG_FILE_NAME = 'module_perun.php';

	const GROUPNAMEPREFIX_ATTR = 'groupNamePrefix';
	const GROUPNAMEAARC_ATTR = 'groupNameAARC';
	const GROUPNAMEAUTHORITY_ATTR = 'groupNameAuthority';

	private $attrName;
	private $groupNamePrefix;
	private $groupNameAARC;
	private $groupNameAuthority;

	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);

		$conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);

		$this->groupNamePrefix = $conf->getString(self::GROUPNAMEPREFIX_ATTR, '');
		$this->groupNameAuthority = $conf->getString(self::GROUPNAMEAUTHORITY_ATTR, '');
		$this->groupNameAARC = $conf->getBoolean(self::GROUPNAMEAARC_ATTR, false);

		if ($this->groupNameAARC && (empty($this->groupNameAuthority) || empty($this->groupNamePrefix))) {
			throw new SimpleSAML_Error_Exception("perun:PerunGroups: 'groupNameAARC' has been set, 'groupNameAuthority' and 'groupNamePrefix' options must be set as well");
		}

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
			if (isset($request["SPMetadata"]["groupNameAARC"]) || $this->groupNameAARC) {
				# https://aarc-project.eu/wp-content/uploads/2017/11/AARC-JRA1.4A-201710.pdf
				# Group name is URL encoded by RFC 3986 (http://www.ietf.org/rfc/rfc3986.txt)
				# Example: urn:geant:elixir-europe.org:group:elixir<groupName>%3A<subGroupName>#perun.elixir-czech.cz
				if (empty($this->groupNameAuthority) || empty($this->groupNamePrefix)) {
					throw new SimpleSAML_Error_Exception("perun:PerunGroups: missing mandatory configuration options 'groupNameAuthority' or 'groupNamePrefix'.");
				}

				$groupName = $this->groupNamePrefix . $group->getName() . '#' . $this->groupNameAuthority;
			} else {
				$groupName = $this->mapGroupName($request, $group->getName());
			}
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
			SimpleSAML\Logger::debug("Mapping $groupName to " . $request["SPMetadata"]["groupMapping"][$groupName] . " for SP " . $request["SPMetadata"]["entityid"]);
			return $request["SPMetadata"]["groupMapping"][$groupName];
		} else if (isset($request["SPMetadata"][self::GROUPNAMEPREFIX_ATTR])) {
			SimpleSAML\Logger::debug("GroupNamePrefix overridden by a SP " . $request["SPMetadata"]["entityid"] . " to " . $request["SPMetadata"][self::GROUPNAMEPREFIX_ATTR]);
			return $request["SPMetadata"][self::GROUPNAMEPREFIX_ATTR] . $groupName;
		} else {
			# No mapping defined
			SimpleSAML\Logger::debug("No mapping found for group $groupName for SP " . $request["SPMetadata"]["entityid"]);
			return $groupName;
		}
	}

}
