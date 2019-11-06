<?php

$config = [
    /**
     * Identifier of Proxy
     */
    'proxyIdentifier' => '',

    /**
     * Name of facility attribute Proxy Identifiers
     */
    'perunProxyIdentifierAttr' => '',

    /**
     * Name of facility attribute Master Proxy Identifier
     */
    'perunMasterProxyIdentifierAttr' => '',

    /**
     * Name of facility attribute EntityID
     */
    'perunProxyEntityIDAttr' => '',

    /**
     * Absolute path, where the metadata will be stored
     */
    'absoluteFileName' => '',

    /**
     * List of attributes definitions (for export)
     */
    'attributesDefinitions' => [
        // Name of attribute from perun => key which will be used in generated metadata
        'perunAttrName' => 'metadataName',
    ],

    /**
     * Attribute map used for extracting info during import from the entity descriptor XML.
     * Map of internal name => Xpath selector string or Xpath selector in array if array should be extracted
     */
    'xml2internal' => [
        'CoCo' => 'boolean(//*[local-name() = "EntityAttributes"]/*[@Name = "http://macedir.org/entity-category"]/'
            . '*[local-name() = "AttributeValue"][text() = "http://www.geant.net/uri/dataprotection-code-of-conduct"])',
        'RaS' => 'boolean(//*[local-name() = "EntityAttributes"]/*[@Name = "http://macedir.org/entity-category"]/*'
            . '[local-name() = "AttributeValue"][text() = "http://refeds.org/category/research-and-scholarship"])',
        'requiredAttributes' => ['//*[local-name() = "RequestedAttribute"][@isRequired = "true"]/@FriendlyName'],
        'loginURL' => 'string(//*[local-name() = "RequestInitiator"]/@Location)',
    ],

    /**
     * Attribute map used for extracting info during import from the SSP array.
     * Map of internal name => flatfile name (nesting by dots) or array of indexes for multiple sources
     */
    'flatfile2internal' => [
        'entityID' => 'entityid',
        'serviceName' => 'name',
        'serviceDescription' => 'description',
        'spInformationURL' => 'url',
        'privacyPolicyURL' => 'UIInfo.PrivacyStatementURL',
        'organizationName' => 'OrganizationName',
        'spOrganizationURL' => 'OrganizationURL',
        'nameIDFormat' => 'NameIDFormat',
        'relayState' => 'RelayState',
        'keys' => 'keys',
        'spAdminContact' => 'contacts',
        'spSupportContact' => 'contacts',
        'assertionConsumerService' => 'AssertionConsumerService',
        'singleLogoutService' => 'SingleLogoutService',
    ],

    /**
     * Attribute map used for storing extracted info in Perun during import.
     * Map of name in Perun => internal name (from xml2internal and flatfile2internal).
     */
    'internal2perun' => [
        'urn:perun:facility:attribute-def:def:CoCo' => 'CoCo',
        'urn:perun:facility:attribute-def:def:RaS' => 'RaS',
        'urn:perun:facility:attribute-def:def:requiredAttributes' => 'requiredAttributes',
        'urn:perun:facility:attribute-def:def:loginURL' => 'loginURL',
        'urn:perun:facility:attribute-def:def:entityID' => 'entityID',
        'urn:perun:facility:attribute-def:def:serviceName' => 'serviceName',
        'urn:perun:facility:attribute-def:def:serviceDescription' => 'serviceDescription',
        'urn:perun:facility:attribute-def:def:spInformationURL' => 'spInformationURL',
        'urn:perun:facility:attribute-def:def:privacyPolicyURL' => 'privacyPolicyURL',
        'urn:perun:facility:attribute-def:def:organizationName' => 'organizationName',
        'urn:perun:facility:attribute-def:def:spOrganizationURL' => 'spOrganizationURL',
        'urn:perun:facility:attribute-def:def:nameIDFormat' => 'nameIDFormat',
        'urn:perun:facility:attribute-def:def:assertionConsumerServices' => 'assertionConsumerService',
        'urn:perun:facility:attribute-def:def:singleLogoutServices' => 'singleLogoutService',
        'urn:perun:facility:attribute-def:def:relayState' => 'relayState',
    ],

    /**
     * Transform attributes before storing in Perun (during import).
     * Array of arrays with string class (of the transformer),
     * array attributes (which are transformed)
     * and array config (passed to the transformer).
     * The transformers should implement the \SimpleSAML\Module\perun\AttributeTransformer interface.
     */
    'transformers' => [
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\EndpointMap',
            'attributes' => ['assertionConsumerService'],
            'config' => ['defaultBinding' => 'HTTP-POST'],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\EndpointMap',
            'attributes' => ['singleLogoutService'],
            'config' => ['defaultBinding' => 'HTTP-Redirect'],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\KeyLists',
            'attributes' => ['keys'],
            'config' => ['purpose2internal' => ['signing' => 'signingCert', 'encryption' => 'encryptionCert']],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\EmailList',
            'attributes' => ['spAdminContact'],
            'config' => ['types' => ['administrative', 'technical']],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\EmailList',
            'attributes' => ['spSupportContact'],
            'config' => ['types' => ['support']],
        ],
    ],
];
