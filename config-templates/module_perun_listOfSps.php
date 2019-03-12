<?php
$config = array(
    /*
     * Unique identifier of proxy
     */
    'proxyIdentifier' => '',

    /*
     * If true, page shows list of SAML2 and OIDC services. If false page shows only SAML2 services
     */
    'showOIDCServices' => true,

    /*
     * Attribute name for facility attribute proxy identifiers
     */
    'perunProxyIdentifierAttr' => '',

    /*
     * Attribute name for facility attribute with loginUrL for service
     */
    'loginUrLAttr' => '',

    /*
     * Attribute name for facility attribute with information, if service is in test environment
     */
    'isTestSpAttr' => '',

    /*
     * Attribute name for facility attribute with information, if facility may be shown on service list or not
     */
    'showOnServiceListAttr' => '',

    /*
     * Attribute name for facility attribute with service EntityId
     */
    'SAML2EntityIdAttr' => '',

    /*
     * Attribute name for facility attribute with service OIDC ClientId
     */
    'OIDCClientIdAttr' => '',

    /*
     * Array of attribute names for facility attributes shown in table
     */
    'attributesDefinitions' => array(),
);
