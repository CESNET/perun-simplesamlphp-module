<?php

/**
 * Filter simply remove all attributes from requests. It is meant to use because We do not want to pass
 * any attributes directly from IdP. Rather fetch all from Perun.
 * Because the attributes should not depends on IdP which user currently used.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

class sspmod_perun_Auth_Process_RemoveAllAttributes extends SimpleSAML_Auth_ProcessingFilter
{

	public function __construct($config, $reserved)
	{
		parent::__construct($config, $reserved);
	}

	public function process(&$request)
	{
		assert('is_array($request)');

		$request['Attributes'] = array();
	}

}
