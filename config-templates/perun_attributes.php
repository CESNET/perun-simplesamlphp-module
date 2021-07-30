<?php

declare(strict_types=1);

$config = [
    /*
     * FACILITY ATTRIBUTES
     */

    'perunFacilityAttr_checkGroupMembership' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:checkGroupMembership',
        'ldap' => 'checkGroupMembership',
        'type' => 'bool',
    ],
    'perunFacilityAttr_voShortNames' => [
        'rpc' => 'urn:perun:facility:attribute-def:virt:voShortNames',
        'ldap' => 'voShortNames',
        'type' => 'map',
    ],
    'perunFacilityAttr_dynamicRegistration' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:dynamicRegistration',
        'ldap' => 'dynamicRegistration',
        'type' => 'bool',
    ],
    'perunFacilityAttr_OIDCClientID' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:OIDCClientID',
        'ldap' => 'OIDCClientID',
        'type' => 'string',
    ],
    'perunFacilityAttr_registerUrl' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:registerUrl',
        'ldap' => 'registrationURL',
        'type' => 'string',
    ],
    'perunFacilityAttr_allowRegistration' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:allowRegistration',
        'ldap' => 'allowRegistration',
        'type' => 'bool',
    ],
    'perunFacilityAttr_registrationURL' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:registrationURL',
        'ldap' => 'registrationURL',
        'type' => 'string',
    ],
    'perunFacilityAttr_wayfFilter' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:wayfFilter',
        'ldap' => 'wayfFilter',
        'type' => 'string',
    ],
    'perunFacilityAttr_wayfEFilter' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:wayfEFilter',
        'ldap' => 'wayfEFilter',
        'type' => 'string',
    ],
    'perunFacilityAttr_reqAups' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:reqAups',
        'ldap' => 'requiredAups',
        'type' => 'map',
    ],
    'perunFacilityAttr_capabilities' => [
        'rpc' => 'urn:perun:facility:attribute-def:def:capabilities',
        'ldap' => 'capabilities',
        'type' => 'map',
    ],

    /*
     * USER ATTRIBUTES
     */

    'perunUserAttribute_einfraid-persistent' => [
        'rpc' => 'urn:perun:user:attribute-def:virt:login-namespace:einfraid-persistent',
        'ldap' => 'einfraid-persistent',
        'type' => 'bool',
    ],
    'perunUserAttribute_einfra' => [
        'rpc' => 'urn:perun:user:attribute-def:def:login-namespace:einfra',
        'ldap' => 'einfra',
        'type' => 'bool',
    ],
    'perunUserAttribute_id' => [
        'rpc' => 'urn:perun:user:attribute-def:core:id',
        'ldap' => 'perunUserId',
        'type' => 'integer',
    ],
    'perunUserAttribute_firstName' => [
        'rpc' => 'urn:perun:user:attribute-def:core:firstName',
        'ldap' => 'firstName',
        'type' => 'string',
    ],
    'perunUserAttribute_middleName' => [
        'rpc' => 'urn:perun:user:attribute-def:core:middleName',
        'ldap' => 'middleName',
        'type' => 'string',
    ],
    'perunUserAttribute_lastName' => [
        'rpc' => 'urn:perun:user:attribute-def:core:lastName',
        'ldap' => 'lastName',
        'type' => 'string',
    ],
    'perunUserAttribute_cn' => [
        'rpc' => 'urn:perun:ues:attribute-def:def:cn',
        'ldap' => 'cn',
        'type' => 'string',
    ],
    'perunUserAttribute_displayName' => [
        'rpc' => 'urn:perun:ues:attribute-def:def:displayName',
        'ldap' => 'displayName',
        'type' => 'string',
    ],
    'perunUserAttribute_mail' => [
        'rpc' => 'urn:perun:ues:attribute-def:def:mail',
        'ldap' => 'mail',
        'type' => 'string',
    ],
    'perunUserAttribute_timezone' => [
        'rpc' => 'urn:perun:user:attribute-def:def:timezone',
        'ldap' => 'timezone',
        'type' => 'string',
    ],
    'perunUserAttribute_preferredLanguage' => [
        'rpc' => 'urn:perun:user:attribute-def:def:preferredLanguage',
        'ldap' => 'preferredLanguage',
        'type' => 'string',
    ],
    'perunUserAttribute_preferredMail' => [
        'rpc' => 'urn:perun:user:attribute-def:def:preferredMail',
        'ldap' => 'preferredMail',
        'type' => 'string',
    ],
    'perunUserAttribute_phone' => [
        'rpc' => 'urn:perun:user:attribute-def:def:phone',
        'ldap' => 'phone',
        'type' => 'string',
    ],
    'perunUserAttribute_address' => [
        'rpc' => 'urn:perun:user:attribute-def:def:address',
        'ldap' => 'address',
        'type' => 'string',
    ],
    'perunUserAttribute_aups' => [
        'rpc' => 'urn:perun:user:attribute-def:def:aups',
        'ldap' => 'aups',
        'type' => 'map',
    ],
    'perunUserAttribute_groupNames' => [
        'rpc' => 'urn:perun:user:attribute-def:virt:groupNames',
        'ldap' => 'groupNames',
        'type' => 'map',
    ],
    'perunUserAttribute_eduPersonEntitlement' => [
        'rpc' => 'urn:perun:user:attribute-def:virt:eduPersonEntitlement',
        'ldap' => 'eduPersonEntitlement',
        'type' => 'map',
    ],
    'perunUserAttribute_entitlement' => [
        'rpc' => 'urn:perun:ues:attribute-def:def:entitlement',
        'ldap' => 'eduPersonEntitlement',
        'type' => 'string',
    ],
    'perunUserAttribute_bonaFideStatus' => [
        'rpc' => 'urn:perun:user:attribute-def:def:bonaFideStatus',
        'ldap' => 'bonaFideStatus',
        'type' => 'map',
    ],
    'perunUserAttribute_eduPersonScopedAffiliations' => [
        'rpc' => 'urn:perun:user:attribute-def:virt:eduPersonScopedAffiliations',
        'ldap' => 'eduPersonScopedAffiliations',
        'type' => 'map',
    ],
    'perunUserAttribute_affiliation' => [
        'rpc' => 'urn:perun:ues:attribute-def:def:affiliation',
        'ldap' => '',
        'type' => 'string',
    ],
    'perunUserAttribute_isCesnetEligibleLastSeen' => [
        'rpc' => 'urn:perun:user:attribute-def:def:isCesnetEligibleLastSeen',
        'ldap' => 'isCesnetEligibleLastSeen',
        'type' => 'string',
    ],
    'perunUserAttribute_eduPersonPrincipalNames' => [
        'rpc' => 'urn:perun:user:attribute-def:virt:eduPersonPrincipalNames',
        'ldap' => 'eduPersonPrincipalNames',
        'type' => 'map',
    ],
    'perunUserAttribute_cesnet' => [
        'rpc' => 'urn:perun:user:attribute-def:def:login-namespace:cesnet',
        'ldap' => 'login;x-ns-einfra',
        'type' => 'string',
    ],
    'perunUserAttribute_einfraid-persistent-shadow' => [
        'rpc' => 'urn:perun:user:attribute-def:def:login-namespace:einfraid-persistent-shadow',
        'ldap' => 'login;x-ns-einfraid-persistent-shadow',
        'type' => 'string',
    ],
    'perunUserAttribute_o' => [
        'rpc' => 'urn:perun:ues:attribute-def:def:o',
        'ldap' => 'o',
        'type' => 'string',
    ],
    'perunUserAttribute_givenName' => [
        'rpc' => 'urn:perun:ues:attribute-def:def:givenName',
        'ldap' => 'givenName',
        'type' => 'string',
    ],
    'perunUserAttribute_sn' => [
        'rpc' => 'urn:perun:ues:attribute-def:def:sn',
        'ldap' => 'sn',
        'type' => 'String',
    ],
    'perunUserAttribute_loa' => [
        'rpc' => 'urn:perun:user:attribute-def:virt:loa',
        'ldap' => 'loa',
        'type' => 'integer',
    ],

    /*
     * GROUP ATTRIBUTES
     */

    'perunGroupAttribute_groupAffiliations' => [
        'rpc' => 'urn:perun:group:attribute-def:def:groupAffiliations',
        'ldap' => 'groupAffiliations',
        'type' => 'map',
    ],

    /*
     * VO ATTRIBUTES
     */

    'perunVoAttribute_aup' => [
        'rpc' => 'urn:perun:vo:attribute-def:def:aup',
        'ldap' => 'aup',
        'type' => 'string',
    ],

    /*
     * RESOURCE ATTRIBUTES
     */

    'perunResourceAttribute_capabilities' => [
        'rpc' => 'urn:perun:resource:attribute-def:def:capabilities',
        'ldap' => 'capabilities',
        'type' => 'map',
    ],
];
