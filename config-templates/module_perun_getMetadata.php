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
     * Map of internal name => flatfile name or array of indexes if nested
     */
    'flatfile2internal' => [
        'entityID' => 'entityid',
        'serviceName' => 'name',
        'serviceDescription' => 'description',
        'spInformationURL' => 'url',
        'privacyPolicyURL' => ['UIInfo', 'PrivacyStatementURL'],
        'organizationName' => 'OrganizationName',
        'spOrganizationURL' => 'OrganizationURL',
        'nameIDFormat' => 'NameIDFormat',
    ],
];
