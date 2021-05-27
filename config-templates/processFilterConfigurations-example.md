## PerunIdentity

Example how to configure PerunIdentity module:
```php
24 => [
        'class' => 'perun:ProxyFilter',
        'filterSPs' => $perunEntityIds,
        'config' => [
                'class' => 'perun:PerunIdentity',
                'uidsAttr' => ['eduPersonUniqueId', 'eduPersonPrincipalName', 'eduPersonTargetedIDString', 'nameid', 'uid'],
                'voShortName' => 'einfra',
                'registerUrlBase' => 'https://perun.cesnet.cz/allfed/registrar',
                'registerUrl' => 'https://login.cesnet.cz/register',
                'interface' => 'ldap',
                'facilityCheckGroupMembershipAttr' => 'urn:perun:facility:attribute-def:def:checkGroupMembership',
                'facilityVoShortNamesAttr' => 'urn:perun:facility:attribute-def:virt:voShortNames',
                'facilityDynamicRegistrationAttr' => 'urn:perun:facility:attribute-def:def:dynamicRegistration',
                'facilityRegisterUrlAttr' => 'urn:perun:facility:attribute-def:def:registerUrl',
                'facilityAllowRegistrationToGroups' => 'urn:perun:facility:attribute-def:def:allowRegistration',
                #MODE: 
                # * FULL - Get user from Perun and check if user has correct rights to access service
                # * USERONLY - Only get user from Perun
                'mode' => 'FULL' #Default value: FULL
        ],
],
```


## IdPAttribute

Example how to enable filter IdPAttribute:

```php
29 => [
        'class' => 'perun:IdPAttribute',
        'attrMap' => [
                'OrganizationName:en' => 'idp_organizationName',
        ],
],
```

'OrganizationName:en' => 'idp_organizationName' means that the $IdPMetadata['Organization']['en'] will be save into 
$request['Attributes']['idp_organizationName']

## EnsureVoMember

Example how to configure filter EnsureVoMember:

```php
31 => array(
    'class' => 'perun:EnsureVoMember',
    'triggerAttr' => 'triggerAttr',
    'voDefsAttr' => 'voDefsAttr',
    'loginURL' => 'https://www.loginUrl.com',
    'registrarURL' => 'https://www.registrarUrl.com',
    'interface' => 'ldap',
),
```

## PerunEntitlement

Example how to enable/configure filter PerunEntitlement:

```php
33 => array(
    'class' => 'perun:PerunEntitlement',
    'interface' => 'ldap',
    'eduPersonEntitlement' => 'eduPersonEntitlement',
    # forwarded entitlement are released by default
    #'releaseForwardedEntitlement' => false, OPTIONAL
    'forwardedEduPersonEntitlement' => 'eduPersonEntitlement',
    #'entityID' => function($request){return empty($request["saml:RequesterID"]) ? $request["SPMetadata"]["entityid"] : $request["saml:RequesterID"][0];},
),
```

## PerunEntitlementExtended

Example how to enable/configure filter PerunEntitlement:

```php
33 => array(
    'class' => 'perun:PerunEntitlementExtended',
    'interface' => 'ldap',
    'outputAttrName' => 'eduPersonEntitlementExtended',
    # forwarded entitlement are released by default
    #'releaseForwardedEntitlement' => false, OPTIONAL
    'forwardedEduPersonEntitlement' => 'eduPersonEntitlement',
    #'entityID' => function($request){return empty($request["saml:RequesterID"]) ? $request["SPMetadata"]["entityid"] : $request["saml:RequesterID"][0];},
),
```

## ForceAup

1.Create these attributes in Perun:
- urn:perun:entityless:attribute-def:def:orgAups
    - Type: LinkedHashMap
    - Unique: no
    - Read: 
    - Write:
   
- urn:perun:user:attribute-def:def:aups
    - Type: LinkedHashMap
    - Unique: no
    - Read: SELF, FACILITY, VO
    - Write: 

- urn:perun:vo:attribute-def:def:aup
    - Type: LargeString
    - Unique: no
    - Read: VO
    - Write: VO
     
- urn:perun:facility:attribute-def:def:reqAups
    - Type: ArrayList
    - Unique: no
    - Read: FACILITY
    - Write: FACILITY
    
    
- urn:perun:facility:attribute-def:virt:voShortNames
    - Type: ArrayList
    - Unique: no
    - Read: FACILITY
    - Write: FACILITY 
    
2.Configure SimpleSAMLphp to use ForceAup:

Example how to enable filter ForceAup:
    
```php
40 => [
    'class' => 'perun:ProxyFilter',
    'filterSPs' => $perunEntityIds,
    'config' => [
        'class' => 'perun:ForceAup',
        'uidAttr' => 'uid',
        'interface' => 'rpc',
        'perunAupsAttr' => 'urn:perun:entityless:attribute-def:def:orgAups',
        'perunUserAupAttr' => 'urn:perun:user:attribute-def:def:aups',
        'perunVoAupAttr' => 'urn:perun:vo:attribute-def:def:aup',
        'perunFacilityReqAupsAttr' => 'urn:perun:facility:attribute-def:def:reqAups',
        'facilityVoShortNames' => 'urn:perun:facility:attribute-def:virt:voShortNames'
    ],
],   
``` 

3.Fill the attributes and set list of required Aups (attr reqAups) and voShortNames (optional) for each facility

