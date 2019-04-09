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

    /**
     * specify which type of IdPListService will be used
     * Expected values: csv, db
     */
    'idpListServiceType' => '',

    /**
     * Specify prefix for filtering AuthnContextClassRef
     * All AuthnContextClassRef values starts with this prefix will be removed before the request will be send to IdP
     */
    'disco.removeAuthnContextClassRefPrefix' => 'urn:cesnet:proxyidp:',

    /**
     *****************************************
     * Part of configuration for status page *
     *****************************************
     */

    /**
     * Specify the used interface to get the status data
     * Only NAGIOS type is now allowed
     */
    'status.type' => 'NAGIOS',

    /**
     * Specify the url for get status information
     */
    'status.nagios.url' => '',

    /**
     * Specify the path to the certicate
     */
    'status.nagios.certificate_path' => '',

    /**
     * Specify the CA dir path
     */
    'status.nagios.ca_path' => '/etc/ssl/certs',

    /**
     * Specify the password for private key
     *
     * OPTIONAL
     */
    'status.nagios.certificate_password' => '',

    /**
     * Specify, if the peer verification is enabled,
     *
     * OPTIONAL
     * Default: false
     */
    'status.nagios.peer_verification' => false,

    /**
     * Specify the list of services, which will be shown
     *
     * OPTIONAL
     * Default: show all received services
     */
    'status.shown_services'=> array(
        'serviceIdentifier' => array(
            'name' => 'serviceName',
            'description' => 'serviceDescription'
        ),
    ),

    /**
     ****************************************
     * Part of configuration for listOfSps  *
     ****************************************
     */

    /**
     * Specify the unique identifier of proxy
     */
    'listOfSps.proxyIdentifier' => '',

    /**
     * If true, page shows list of SAML2 and OIDC services. If false page shows only SAML2 services
     */
    'listOfSps.showOIDCServices' => true / false,

    /**
     * Specify attribute name for facility attribute proxy identifiers
     */
    'listOfSps.perunProxyIdentifierAttr' => '',

    /**
     * Specify attribute name for facility attribute with loginUrL for service
     */
    'listOfSps.loginURLAttr' => '',

    /**
     * Specify attribute name for facility attribute with information, if service is in test environment
     */
    'listOfSps.isTestSpAttr' => '',

    /**
     * Specify attribute name for facility attribute with information, if facility may be shown on service list or not
     */
    'listOfSps.showOnServiceListAttr' => '',

    /**
     * Specify attribute name for facility attribute with service EntityId
     */
    'listOfSps.SAML2EntityIdAttr' => '',

    /**
     * Specify attribute name for facility attribute with service OIDC ClientId
     */
    'listOfSps.OIDCClientIdAttr' => '',

    /**
     * Specify list of facility attributes, which will be shown
     */
    'listOfSps.attributesDefinitions' => array(
        ''
    ),


);
