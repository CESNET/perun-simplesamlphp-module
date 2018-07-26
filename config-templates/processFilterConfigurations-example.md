## PerunIdentity

Example how to enable and configure PerunIdentity filter

```php
24 => array(
        'class' => 'perun:PerunIdentity',
        'uidsAttr' => array('eduPersonUniqueId', 'eduPersonPrincipalName', 'eduPersonTargetedIDString', 'nameid', 'uid'),
        'voShortName' => 'einfra',
        'registerUrl' => 'https://login.cesnet.cz/register',
        'interface' => 'ldap',
        'checkGroupMembershipAttr' => 'urn:perun:facility:attribute-def:def:checkGroupMembership',
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
