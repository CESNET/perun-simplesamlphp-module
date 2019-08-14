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

