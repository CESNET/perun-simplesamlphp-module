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
