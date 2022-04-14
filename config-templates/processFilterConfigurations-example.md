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
                'useAdditionalIdentifiersLookup' => true,
                'additionalIdentifiersAttribute' => 'additionalIdentifiers',
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
        'interface' => 'rpc',
        'perunAupsAttr' => 'urn:perun:entityless:attribute-def:def:orgAups',
        'perunUserAupAttr' => 'urn:perun:user:attribute-def:def:aups',
        'perunVoAupAttr' => 'urn:perun:vo:attribute-def:def:aup',
        'perunFacilityReqAupsAttr' => 'urn:perun:facility:attribute-def:def:reqAups',
        'perunFacilityVoShortNamesAttr' => 'urn:perun:facility:attribute-def:virt:voShortNames'
    ],
],   
``` 

3.Fill the attributes and set list of required Aups (attr reqAups) and voShortNames (optional) for each facility

## AttributeMap

This filter maps attribute names according to a service specific map from Perun. It can be used to achieve compatibility with a SP which requires specific non-standard attribute names.

Example how to enable filter AttributeMap:

```php
101 => [
    'class' => 'perun:AttributeMap',
    'attrMapAttr' => 'attrWhichContainsAttrMap', # expected structure: targetAttribute => sourceAttribute
    // 'keepSourceAttributes' => true, # optional, whether keep source attributes or remove them, default false
    // 'entityid' => 'EntityIdOfTheService', # optional, string or callable, defaults to current SP's entity ID
    // 'interface' => 'rpc', # optional, rpc/ldap, default rpc
],
```

## ExtractRequestAttribute

Filter is intended to extract an attribute specified by set of keys forming the chain of keys in the `$request` variable into the configured destination attribute.

Configuration options:
* `destination_attr_name`: specifies attribute name, into which the extracted value will be stored
* `request_keys`: string, which contains a semicolon (`;`) separated chain of keys that are examined in the state. Numeric keys are automatically treated as array indexes. For instance, value `'saml:AuthenticatingAuthority;0'` will be treated as code `$request['saml:AuthenticatingAuthority'][0]`. In case of this value being empty, exception is thrown. Otherwise, extracted value is stored into the configured destination attribute.
* `fail_on_nonexisting_keys`: `true` or `false`, specifies if in case of missing key in the request variable the filter should terminate with an exception or not
* `default_value`: array, which will be set as default value, if the configured keys did not lead to value

```php
// EXTRACT AUTHENTICATING ENTITY INTO authenticating_idp attribute
1 => [ 
    'class' => 'perun:ExtractRequestAttribute',
    'destination_attr_name' => 'authenticating_idp',
    'request_keys' => 'saml:AuthenticatingAuthority;0',
    'fail_on_nonexisting_keys' => 'true',
    'default_value' => [],
],
```

## PerunUser

Filter tries to identify the Perun user. It uses the combination of user identifier and IdP identifier to find the user (or to be more precise, the user identity and associated user account). If it can, the user object is set to `$request` parameter into `$request[PerunConstants::PERUN][PerunConstants::USER]`. Otherwise, user is forwarded to configured registration.

Configuration options:
* `interface`: specifies what interface of Perun should be used to fetch data. See class `SimpleSAML\Module\perun\PerunAdapter` for more details.
* `uid_attrs`: list of attributes that contain user identifiers to be used for identification. The order of the items in the list represents the priority.
* `idp_id_attr`: name of the attribute (from `$request['Attributes']` array), which holds EntityID of the identity provider that has performed the authentication.
* `register_url`: URL to which the user will be forwarded for registration. Leave empty to use the Perun registrar.
* `callback_parameter_name`: name of the parameter wich will hold callback URL, where the user should be redirected after the registration on URL configured in the `register_url` property.
* `perun_register_url`: the complete URL (including vo and group) to which user will be redirected, if `register_url` has not been configured. Parameters targetnew, targetexisting and targetextended will be set to callback URL to continue after the registration is completed.
* `use_additional_identifiers_lookup`: `true` or `false`, set it to `true` if you want to use additionalIdentifiers as fallback lookup method if the standard one fails.
* `additional_identifiers_attribute`: name of the attribute (from `$request['Attributes']` array), which holds the additional identifiers. If you use RPC adapter, the value in the attribute resolved using `idp_id_attr` will be used as well for locating the user in Perun. 

```php
2 => [
    'class' => 'perun:PerunUser',
    'interface' => 'LDAP',
    'uid_attrs' => ['eduPersonUniqueId', 'eduPersonPrincipalName'],
    'idp_id_attr' => 'authenticating_idp',
    'register_url' => 'https://signup.cesnet.cz/',
    'callback_parameter_name' => 'callback',
    'perun_register_url' => 'https://signup.perun.cesnet.cz/fed/registrar/?vo=cesnet',
    'use_additional_identifiers_lookup' => true,
    'additional_identifiers_attribute' => 'additionalIdentifiers',
],
```

## PerunAup

Filter fetches the given attribute holding approved AUP and checks, if expected value is set in the attribute or not. If not, it redirects the user to specified registration component, where user will be asked to approve the AUP.

Configuration options:
* `interface`: specifies what interface of Perun should be used to fetch data. See class `SimpleSAML\Module\perun\PerunAdapter` for more details.
* `attribute`: name of the attribute, which will be fetched from Perun and holds the value of approved AUP.
* `value`: value that is expected in the attribute as mark of approved AUP. Expected is a string.
* `approval_url`: URL to which the user will be forwarded for registration. Leave empty to use the Perun registrar.
* `callback_parameter_name`: name of the parameter wich will hold callback URL, where the user should be redirected after the AUP approval on URL configured in the `approval_url` property.
* `perun_register_url`: the complete URL (including vo and group) to which user will be redirected, if `approval_url` has not been configured. Parameters targetnew, targetexisting and targetextended will be set to callback URL to continue after the AUP approval is completed.

```php
3 => [
    'class' => 'perun:PerunAup',
    'interface' => 'LDAP',
    'value' => 'aup_2020_01_01',
    'attribute' => 'approved_aup',
    'approval_url' => 'https://signup.cesnet.cz/aup/',
    'callback_parameter_name' => 'callback',
    'perun_approval_url' => 'https://signup.perun.cesnet.cz/fed/registrar/?vo=cesnet&group=aup'
],
```

## DropUserAttributes

Drops specified user attributes from the `$request['Attributes']` variable.

Configuration options:
* `attribute_names`: list of attribute names which will be dropped.

```php
10 => [
    'class' => 'perun:DropUserAttributes',
    'attribute_names' => ['aup', 'eppn', 'eduPersonTargetedID']
],
```

## QualifyNameID

Adds qualifiers into NameID based on the configuration

Configuration options:
* `name_id_attribute`: Attribute (NameID) which should be qualified
* `name_qualifier_attribute`: User attribute with value, which will be set as the NameQualifier part of the NameID. Leave empty to use static value configured via option `name_qualifier`.
* `name_qualifier`: Static value which will be set as the NameQualifier part of the NameID.
* `sp_name_qualifier_attribute`: User attribute with value, which will be set as the SPNameQualifier part of the NameID. Leave empty to use static value configured via option `sp_name_qualifier`.
* `sp_name_qualifier`: Static value which will be set as the SPNameQualifier part of the NameID.

```php
11 => [
    'class' => 'perun:QualifyNameID',
    'name_id_attribute' => 'eduPersonTargetedID',
    'name_qualifier_attribute' => 'SourceIdPEntityID',
    'name_qualifier' => 'https://login.cesnet.cz/idp/',
    'sp_name_qualifier_attribute' => 'ProxyEntityID',
    'sp_name_qualifier' => 'https://login.cesnet.cz/proxy/',
],
```

## GenerateIdPAttributes

Gets metadata of the IdP specified by `idp_identifier_attribute` value and tries to set the specified keys from IdP metadata into attributes.

Configuration options:
* `idp_identifier_attribute`: Attribute holding the identifier of the Authenticating IdP
* `attribute_map`: Map of IdP metadata attributes, where keys are the colon separated keys that will be searched in IdP metadata and values are the destination attribute names.

```php
20 => [
    'class' => 'perun:GenerateIdPAttributes',
    'idp_identifier_attribute' => 'sourceIdPEntityID',
    'attribute_map' => [
        'name:en' => 'sourceIdPName',
        'OrganizationName:en' => 'sourceIdPOrganizationName',
        'OrganizationURL:en' => 'sourceIdPOrganizationURL',
    ],
],
```
## SpAuthorization

Performs authorization check define dby the SP based on group membership in Perun. User has to be valid member of at least one of the groups assigned to resources of the facility representing the service. If not satisfied, the filter check if registration is enabled. In case of enabled registration, user is forwarded to custom registration link (if configured), or to a dynamic form, where user will select the combination of VO and group to which he/she applies for access. Form then forwards user to Perun registration component. In all other cases, user is forwarded to access denied page.
NOTE: for correct functionality, RPC adapter must be available, as other adapters cannot fetch info about what groups allow registration (have registration forms) and similar data.

Configuration options:
* `interface`: specifies what interface of Perun should be used to fetch data. See class `SimpleSAML\Module\perun\PerunAdapter` for more details.
* `registrar_url`: URL where Perun registration component is located. Expected URL is the base, without any parameters.
* `check_group_membership_attr`: mapping to the attribute containing flag, if membership check should be performed.
* `vo_short_names_attr`: mapping to the attribute containing shortnames of the VOs for which the service has resources (gives access to the groups).
* `registration_link_attr`: mapping to the attribute containing custom service registration link. Filter adds the callback URL, to which to redirect user after the registration, as query string in form of 'callback=URL'.
* `allow_registration_attr`: mapping to the attribute containing flag, if registration in case of denied access is enabled
* `handle_unsatisfied_membership`: whether handle unsatisfied membership

```php
25 => [
    'class' => 'perun:SpAuthorization',
    'interface' => 'ldap',
    'registrar_url' => 'https://signup.perun.cesnet.cz/fed/registrar/',
    'check_group_membership_attr' => 'check_group_membership',
    'vo_short_names_attr' => 'vo_short_names',
    'registration_link_attr' => 'registration_link',
    'allow_registration_attr' => 'allow_registration',
    'handle_unsatisfied_membership' => true,
],
```

## EnsureVOMember

Checks whether the user is in the given VO (group). If not, redirects him/her to the registration.

Configuration options:
* `registrationUrl`: URL to the registration
* `voShortName`: VO shortname to check the user's membership
* `groupName`: OPTIONAL, checks that user is in given group
* `callbackParameterName`: name of the parameter wich will hold callback URL, where the user should be redirected after the AUP approval on URL configured in the `approval_url` property,
* `interface`: specifies what interface of Perun should be used to fetch data. See class `SimpleSAML\Module\perun\PerunAdapter` for more details.
```php
25 => [
    'class' => 'perun:PerunEnsureMember',
    'registerUrl' => 'https://signup.perun.cesnet.cz/fed/registrar/',
    'voShortName' => 'cesnet',
    'groupName' => 'cesnet_group_name', // optional
    'callbackParameterName' => 'targetnew',
    'interface' => 'ldap',
],
```
