## PerunIdentity

Example how to configure PerunIdentity module:
```php
24 => array(
        'class' => 'perun:ProxyFilter',
        'filterSPs' => $perunEntityIds,
        'config' => array(
                'class' => 'perun:PerunIdentity',
                'uidsAttr' => array('eduPersonUniqueId', 'eduPersonPrincipalName', 'eduPersonTargetedIDString', 'nameid', 'uid'),
                'voShortName' => 'einfra',
                'registerUrlBase' => 'https://perun.cesnet.cz/allfed/registrar',
                'registerUrl' => 'https://login.cesnet.cz/register',
                'interface' => 'ldap',
                'facilityCheckGroupMembershipAttr' => 'urn:perun:facility:attribute-def:def:checkGroupMembership',
                'facilityVoShortNamesAttr' => 'urn:perun:facility:attribute-def:virt:voShortNames',
                'facilityDynamicRegistrationAttr' => 'urn:perun:facility:attribute-def:def:dynamicRegistration',
                'facilityRegisterUrlAttr' => 'urn:perun:facility:attribute-def:def:registerUrl',
                'facilityAllowRegistrationToGroups' => 'urn:perun:facility:attribute-def:def:allowRegistration',
        ),
),
```


## IdPAttribute

Example how to enable filter IdPAttribute:

```php
29 => array(
        'class' => 'perun:IdPAttribute',
        'attrMap' => array(
                'OrganizationName:en' => 'idp_organizationName',
        ),
),
```

'OrganizationName:en' => 'idp_organizationName' means that the $IdPMetadata['Organization']['en'] will be save into 
$request['Attributes']['idp_organizationName']
