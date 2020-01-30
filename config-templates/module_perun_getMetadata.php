<?php

use SimpleSAML\Module\perun\MetadataToPerun;

$config = [
    /**
     * Identifier of Proxy
     */
    'proxyIdentifier' => '',

    /**
     * Name of facility attribute Proxy Identifiers
     */
    'perunProxyIdentifierAttr' => 'urn:perun:facility:attribute-def:def:proxyIdentifiers',

    /**
     * Name of facility attribute Master Proxy Identifier
     */
    'perunMasterProxyIdentifierAttr' => 'urn:perun:facility:attribute-def:def:masterProxyIdentifier',

    /**
     * Name of facility attribute EntityID
     */
    'perunProxyEntityIDAttr' => 'urn:perun:facility:attribute-def:def:entityID',

    /**
     * Name of facility attribute isSamlFacility (optional)
     */
    'perunIsSamlFacilityAttr' => 'urn:perun:facility:attribute-def:def:isSamlFacility',

    /**
     * Absolute path, where the metadata will be stored
     */
    //'absoluteFileName' => '',

    /**
     * List of attributes definitions (for export)
     */
    'attributesDefinitions' => [
        // Name of attribute from perun => key which will be used in generated metadata
        'urn:perun:facility:attribute-def:def:entityID' => 'entityid',
        'urn:perun:facility:attribute-def:def:serviceName' => 'name',
        'urn:perun:facility:attribute-def:def:serviceDescription' => 'description',
        'urn:perun:facility:attribute-def:def:spInformationURL' => 'url',
        'urn:perun:facility:attribute-def:def:privacyPolicyURL' => 'privacypolicy',
        'urn:perun:facility:attribute-def:def:organizationName' => 'OrganizationName',
        'urn:perun:facility:attribute-def:def:spOrganizationURL' => 'OrganizationURL',
        'urn:perun:facility:attribute-def:def:assertionConsumerServices' => 'AssertionConsumerService',
        'urn:perun:facility:attribute-def:def:singleLogoutServices' => 'SingleLogoutService',
        'urn:perun:facility:attribute-def:def:relayState' => 'RelayState',
        'urn:perun:facility:attribute-def:def:requiredAttributes' => 'attributes',
        'urn:perun:facility:attribute-def:def:nameIDFormat' => 'NameIDFormat',
        'urn:perun:facility:attribute-def:def:signingCert' => 'signingCert',
        'urn:perun:facility:attribute-def:def:encryptionCert' => 'encryptionCert',
    ],

    /**
     * Transform attributes after retrieving from Perun (during export).
     * Array of arrays with string class (of the transformer),
     * array attributes (which are transformed)
     * and array config (passed to the transformer).
     * The transformers should implement the \SimpleSAML\Module\perun\AttributeTransformer interface.
     */
    'exportTransformers' => [
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\EndpointMapToArray',
            'attributes' => ['AssertionConsumerService'],
            'config' => ['defaultBinding' => 'HTTP-POST'],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\EndpointMapToArray',
            'attributes' => ['SingleLogoutService'],
            'config' => ['defaultBinding' => 'HTTP-Redirect'],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\KeyListsToArray',
            'attributes' => ['signingCert', 'encryptionCert'],
            'config' => [
                'purposes' => ['signingCert' => 'signing', 'encryptionCert' => 'encryption'],
                'outputKeys' => 'keys',
                'outputCertData' => 'certData',
            ],
        ],
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
        'entityCategory' => ['//*[local-name() = "EntityAttributes"]/*[@Name = "http://macedir.org/entity-category"]/*'
            . '[local-name() = "AttributeValue"]', ],
        MetadataToPerun::SERVICE_NAME => [
            MetadataToPerun::XPATH_LANG => '//*[local-name() = "UIInfo"]/*[local-name() = "DisplayName"]'
        ],
        MetadataToPerun::SERVICE_DESCRIPTION => [
            MetadataToPerun::XPATH_LANG => '//*[local-name() = "UIInfo"]/*[local-name() = "Description"]'
        ],
        'spInformationURL' => '//*[local-name() = "UIInfo"]/*[local-name() = "InformationURL"]',
        'privacyPolicyURL' => '//*[local-name() = "UIInfo"]/*[local-name() = "PrivacyStatementURL"]',
        MetadataToPerun::ORGANIZATION_NAME => '//*[local-name() = "Organization"]/*[local-name() = "OrganizationName"]',
        'spOrganizationURL' => '//*[local-name() = "Organization"]/*[local-name() = "OrganizationURL"]',
        'nameIDFormat' => ['//*[local-name() = "NameIDFormat"]'],
        'signingCert' => ['//*[local-name() = "KeyDescriptor" and (not(@use) or @use="signing")]//*[local-name() = "X509Certificate"]'],
        'encryptionCert' => ['//*[local-name() = "KeyDescriptor" and (not(@use) or @use="encryption")]//*[local-name() = "X509Certificate"]'],
        'spAdminContact' => ['//*[local-name() = "ContactPerson" and (@contactType="technical" or @contactType="administrative")]/*[local-name() = "EmailAddress"]'],
        'spSupportContact' => ['//*[local-name() = "ContactPerson" and (@contactType="support")]/*[local-name() = "EmailAddress"]'],
        'assertionConsumerService' => ['@Binding' => '//*[local-name() = "AssertionConsumerService"]/@Location'],
        'singleLogoutService' => ['@Binding' => '//*[local-name() = "SingleLogoutService"]/@Location'],
    ],

    /**
     * Attribute map used for extracting info during import from the SSP array.
     * Map of internal name => flatfile name (nesting by dots) or array of indexes for multiple sources
     */
    'flatfile2internal' => [
        MetadataToPerun::ENTITY_ID => 'entityid',
        MetadataToPerun::SERVICE_NAME => 'name',
        MetadataToPerun::SERVICE_DESCRIPTION => 'description',
        'spInformationURL' => 'url',
        'privacyPolicyURL' => 'UIInfo.PrivacyStatementURL',
        MetadataToPerun::ORGANIZATION_NAME => 'OrganizationName',
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
        'urn:perun:facility:attribute-def:def:entityID' => \SimpleSAML\Module\perun\MetadataToPerun::ENTITY_ID,
        'urn:perun:facility:attribute-def:def:serviceName' => \SimpleSAML\Module\perun\MetadataToPerun::SERVICE_NAME,
        'urn:perun:facility:attribute-def:def:serviceDescription' => \SimpleSAML\Module\perun\MetadataToPerun::SERVICE_DESCRIPTION,
        'urn:perun:facility:attribute-def:def:spInformationURL' => 'spInformationURL',
        'urn:perun:facility:attribute-def:def:privacyPolicyURL' => 'privacyPolicyURL',
        'urn:perun:facility:attribute-def:def:organizationName' => \SimpleSAML\Module\perun\MetadataToPerun::ORGANIZATION_NAME,
        'urn:perun:facility:attribute-def:def:spOrganizationURL' => 'spOrganizationURL',
        'urn:perun:facility:attribute-def:def:nameIDFormat' => 'nameIDFormat',
        'urn:perun:facility:attribute-def:def:assertionConsumerServices' => 'assertionConsumerService',
        'urn:perun:facility:attribute-def:def:singleLogoutServices' => 'singleLogoutService',
        'urn:perun:facility:attribute-def:def:relayState' => 'relayState',
        'urn:perun:facility:attribute-def:def:signingCert' => 'signingCert',
        'urn:perun:facility:attribute-def:def:encryptionCert' => 'encryptionCert',
        'urn:perun:facility:attribute-def:def:spAdminContact' => 'spAdminContact',
        'urn:perun:facility:attribute-def:def:spSupportContact' => 'spSupportContact',
        'urn:perun:facility:attribute-def:def:entityCategory' => 'entityCategory',
        'urn:perun:facility:attribute-def:def:proxyIdentifiers' => 'proxyIdentifiers',
    ],

    /**
     * Transform attributes before storing in Perun (during import).
     * Array of arrays with string class (of the transformer),
     * array attributes (which are transformed)
     * and array config (passed to the transformer).
     * The transformers should implement the \SimpleSAML\Module\perun\AttributeTransformer interface.
     */
    'importTransformers' => [
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
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\ShibbolethAttributeFilter',
            'attributes' => ['requiredAttributes', 'entityCategory'],
            'config' => [
                'tagsAttribute' => 'proxyIdentifiers',
                'entityCategoriesAttribute' => 'entityCategory',
                'attributesAttribute' => 'requiredAttributes',
                'skipDefault' => false,
                'ignore.attributes' => [
                    'programme', 'field', 'national', 'degree', 'isTeacher', 'principal', 'encTest',
                ],
                'ignore.entityIDs' => [
                    'google.com',
                    'https://engine.elixir-czech.org/authentication/sp/metadata',
                    'https://filesender.cesnet.cz/saml/sp',
                    'https://filesender2.cesnet.cz/saml/sp',
                    'https://foodle.cz/sp',
                    'https://www2.foodle.cz/sp',
                    'https://dev1.redclara.net/shibboleth',
                    'https://sivic.redclara.net/shibboleth',
                    'https://wiki2.redclara.net/shibboleth',
                    'https://colaboratorio.redclara.net/shibboleth',
                    'https://projekty-test.ics.muni.cz/sp/shibboleth',
                    'https://projekty2.ics.muni.cz/sp/shibboleth',
                    'https://wiki.ics.muni.cz/shibboleth',
                    'https://montgomery-dev.ucn.muni.cz/shibboleth/cztestfed/sp',
                    'https://perun.metacentrum.cz/shibboleth/forceAuthn',
                    'http://adfs.ceitec.cz/adfs/services/trust',
                    // without metadata
                    'urn:urkund:shibboleth:sp',
                    'https://eventos.redclara.net/shibboleth-sp',
                    'http://adfs.geant.org/adfs/services/trust',
                    'https://webdev.hwwilsonweb.com/shibboleth',
                    'https://shibboleth.highwire.org/applications/secure-sp',
                    'https://scauth.scopus.com/',
                    'https://shibboleth2sp.jstor.org/shibboleth',
                    'https://www.medigrid.cz/shibboleth/cztestfed/sp',
                    'https://despil.fi.muni.cz/shibboleth/cztestfed/sp',
                    'https://vpn2.muni.cz/shibboleth/cztestfed/sp',
                    'https://moodlinka.ped.muni.cz/sp/shibboleth',
                    'https://www.cerit.cz/sp/shibboleth',
                    'https://obiwan.ics.muni.cz/kic/ezdroje/',
                    'https://zetes.fi.muni.cz/secure',
                    'https://webcentrum.muni.cz/sp/shibboleth',
                    'https://gehir.phil.muni.cz/sp/shibboleth',
                    'urn:mace:cesnet.cz:cztestfed:muni.cz',
                    'https://portal.lf1.cuni.cz/shibboleth/cztestfed/sp',
                    'https://mefanet-motol.cuni.cz/shibboleth/cztestfed/sp',
                    'https://portal.lf3.cuni.cz/shibboleth/cztestfed/sp',
                    'https://mefanet.lfhk.cuni.cz/shibboleth/cztestfed/sp',
                    'https://shibboleth.lf1.cuni.cz/shibboleth/',
                    'https://portal.lf1.cuni.cz/shibboleth/',
                    'https://vidcon.cesnet.cz/shibboleth/cztestfed/sp',
                ],
                'entityCategories' => [
                    'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' => [
                        'cn',
                        'eduPersonPrincipalName',
                        'eduPersonScopedAffiliation',
                        'email',
                        'givenName',
                        'sn',
                        'tcsSchacHomeOrg',
                    ],
                    'http://refeds.org/category/research-and-scholarship' => [
                        'displayName',
                        'eduPersonPrincipalName',
                        'eduPersonScopedAffiliation',
                        'eduPersonTargetedID',
                        'email',
                    ],
                    'https://inacademia.org/metadata/inacademia-simple-validation.xml' => [
                        'cn',
                        'eduPersonPrincipalName',
                        'eduPersonScopedAffiliation',
                        'eduPersonUniqueId',
                        'email',
                        'givenName',
                        'sn',
                        'tcsSchacHomeOrg',
                        'transientId',
                    ],
                ],
                'file' => __DIR__ . '/attribute-filter.xml',
                //'xml' => '...',
            ],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\AttributeAlter',
            'attributes' => ['proxyIdentifiers'],
            'config' => [
                'pattern' => '/^release(To)?/',
                'replacement' => '',
            ],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\AttributeAlter',
            'attributes' => ['proxyIdentifiers'],
            'config' => [
                'pattern' => '/^(All|ScopedAffiliation|Mail|TargetedID|Entitlement|eduroamUID)$/',
                '%remove',
            ],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\AttributeAlter',
            'attributes' => ['proxyIdentifiers'],
            'config' => [
                'pattern' => '/^/',
                'replacement' => 'https://idp2.ics.muni.cz/idp/shibboleth#',
            ],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\LocalesToLanguages',
            'attributes' => [
                'serviceName',
                'serviceDescription',
                'organizationName',
                'spInformationURL',
                'spOrganizationURL'
            ],
        ],
        [
            'class' => '\\SimpleSAML\\Module\\perun\\transformers\\FlatMap',
            'attributes' => [
                'serviceName',
                'serviceDescription',
                'organizationName',
                'spInformationURL',
                'spOrganizationURL'
            ],
        ],
    ],
];
