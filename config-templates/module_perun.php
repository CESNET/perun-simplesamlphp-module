<?php

/**
 * This is example configuration of SimpleSAMLphp Perun interface and additional features.
 * Copy this file to default config directory and edit the properties.
 *
 * copy command (from SimpleSAML base dir)
 * cp modules/perun/module_perun.php config/
 */
$config = array(

	/**
	 * base url to rpc with slash at the end.
	 */
	'rpc.url' => 'https://perun.inside.cz/krb/rpc/',

	/**
	 * rpc credentials if rpc url is protected with basic auth.
	 */
	'rpc.username' => '_proxy-idp',
	'rpc.password' => 'password',

	/**
	 * hostname of perun ldap with ldap(s):// at the beginning.
	 */
	'ldap.hostname' => 'ldaps://perun.inside.cz',

	'ldap.base' => 'dc=perun,dc=inside,dc=cz',

	/**
	 * ldap credentials if ldap search is protected. If it is null or not set at all. No user is used for bind.
	 */
	//'ldap.username' => '_proxy-idp',
	//'ldap.password' => 'password'

	/**
	 * specify if disco module should filter out IdPs which are not whitelisted neither commited to CoCo or RaS.
	 * default is false.
	 */
	//'disco.disableWhitelisting' => true,

);
