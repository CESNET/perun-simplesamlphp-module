## [6.0.6](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.0.5...v6.0.6) (2021-08-19)


### Bug Fixes

* fix bad import of Exceptions ([bdd51b4](https://github.com/CESNET/perun-simplesamlphp-module/commit/bdd51b46edb765270d282c302176aaf3e01b9117))

## [6.0.5](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.0.4...v6.0.5) (2021-08-18)


### Bug Fixes

* 🐛 fix not checking for key existence in aups ([00cf0f0](https://github.com/CESNET/perun-simplesamlphp-module/commit/00cf0f0b53b1f4828cf12208976124a06a72bafa))
* 🐛 refactored AUPs DateTime treatment in ForceAup ([5130dfc](https://github.com/CESNET/perun-simplesamlphp-module/commit/5130dfc37b4f6bb847ee09d0f7ae57ffb8809477))

## [6.0.4](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.0.3...v6.0.4) (2021-08-18)


### Bug Fixes

* fix bad return type in DatabaseCommand ([95328ba](https://github.com/CESNET/perun-simplesamlphp-module/commit/95328bab777f595683342aa77141a3b1e6bb7d10))

## [6.0.3](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.0.2...v6.0.3) (2021-08-18)


### Bug Fixes

* fix duplicate lines in challenges ([360db1a](https://github.com/CESNET/perun-simplesamlphp-module/commit/360db1adf6b9bd1d00e0edc72ebaf2ee41961dc4))

## [6.0.2](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.0.1...v6.0.2) (2021-08-18)


### Bug Fixes

* Refactor ForcAup filter ([7ef157e](https://github.com/CESNET/perun-simplesamlphp-module/commit/7ef157e6023db9afdfde455e4331970ce9c2f9a8))

## [6.0.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.0.0...v6.0.1) (2021-08-10)


### Bug Fixes

* fix processing attr val  of map type in LDAP ([d892ca9](https://github.com/CESNET/perun-simplesamlphp-module/commit/d892ca944d92b0de2e821d4a4421cce84bc5b514))

# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]

## [v6.0.0]
#### Changed
- Improve WAYF searching by localized name and domain
- Implemented filter EnsureVoMember
- Security improvements in script calls

#### Fixed
- Detailed endpoint format when spaced in EndpointMapToArray 
- Revert change to INDEX_MIN in EndpointMapToArray
- Rename the hook correctly to naming convention
- Each log has just one line output

## [v5.2.0]
#### Added
- Added possibility to use a callable for entityID parameter in PerunEntitlement(Extended)

## [v5.1.1]
#### Fixed
- Fixed removal of filtered authnContextClassRefs in disco
 
## [v5.1.0]
#### Added
- Added possibility to add custom texts to the TEST_SP warning page.

#### Changed
- Use translation  for privacy policy document block on consent screen from module Perun
- Connection to the database obtained through the SimpleSAML Database class

#### Fixed
- Fixed bad check in NagiosStatusConnector.php

## [v5.0.0]
#### Added
- Added extended PerunEntitlements

#### Changed
- Refactored Disco page. See the config template for example configuration.
- Obtaining the data from Nagios is done through SSH instead of a certificate and calling an API

#### Fixed
- Fixed bug in PerunAttributes.php for PARTIAL mode when mapping one Perun attribute to more internal attributes 
  caused getting attributes from Perun every time.

## [v4.1.1]
#### Fixed
- Fixed bad log message in PerunIdentity in mode USERONLY

## [v4.1.0]
#### Changed
- Allow using Perun RPC serializer from the configuration. Default value is 'json'. 
- Add new option 'mode' for PerunIdentity process filter:
    - mode: 'FULL' - Get the user from Perun and check if user has correct rights to access service
    - mode: 'USERONLY' - Get the user from Perun only

## [v4.0.4]
#### Fixed
- Fixed getting SP name from 'UIInfo>DisplayName'

## [v4.0.3]
#### Fixed
- Fixed works with internal attr name in MetadataToPerun/MetadataFromPerun

## [v4.0.2]
#### Fixed
- Fixed getting attributes from Perun in partial mode
    - Allow to store one source attribute to more destination attributes

## [v4.0.1]
#### Fixed
- Fixed getting attributes in class ForceAup

## [v4.0.0]
#### Added
- Added some methods for getting values to Adapter.php
- Added fallback to RPC for methods we're not able to run in LDAP
- Add getFacilityAdmins method to RPC Connector

#### Changed
- Changed the way of getting attribute names for interfaces: through internal attribute names in perun_attributes.php config
- Return sorted eduPersonEntitlement
- Don't show previous selection when user show all entries on the discovery page
- ListOfSps 
    - Don't show the description by default
    - Added required attribute 'listOfSps.serviceNameAttr' !!!
    - Add translation for multi-languages attributes

#### Fixed
- Fixed Updating UES in Perun

## [v3.9.0]
#### Added
- Added facility capabilities to PerunEntitlement
- Added process filter for logging info about login

#### Changed
- Use object `Configuration` for getting base module configuration
- Add possibility to select mode(whitelist/blacklist) in ProxyFilter.php
    * The default option is blacklist
- Allow call multiple ProcessFilter in one ProxyFilter module

#### Fixed
- Fixed the width of showed tagged idps in case the count of idps is equal to (x * 3) + 1
- Using try{}catch{} to avoid to PerunException in PerunEntitlement.php
- Return [] instead of null in getFacilityCapability via RPC, if facilityCapability is not set

## [v3.8.0]
#### Changed
- Releasing forwardedEduPersonEntitlement is now optional (forwardedEduPersonEntitlement are released by default)

#### Fixed
- Fixed problem with getting group without description from LDAP 
    * Before: Exeption
    * Now: Description is ''
- Fixed releasing entitlement for Virtual Organization
    * Before: einfra:members
    * Now: einfra

#### Removed
- Removed deprecated getFacilitiesByEntityId method

## [v3.7.4]
#### Added
- Added logging response time for each request into RPC/LDAP

#### Changed
- If needed to get more facility attributes, method getFacilityAttributesValues() is used instead of several calls of getFacilityAttribute()

#### Fixed
- Fix logging request params

## [v3.7.3]
#### Fixed
- Fixed the bug from [bc3ec33] which caused that the updating UES didn't work.
- Use the same prefix for all messages in updateUes.php

[bc3ec33]:https://github.com/CESNET/perun-simplesamlphp-module/commit/bc3ec33c8f5088f7be712b8e5a0e70f229731648

## [v3.7.2]
#### Fixed
- Allow omitted config for nested class in ProxyFilter
- Fixed bad call of function 'showTaggedEntry()'

## [v3.7.1]
#### Fixed
- Using correct const for EntitlementPrefix in PerunEntitlement.php
- Added missing 'group' between entitlementPrefix and groupName in mapGroupName()

## [v3.7.0]
#### Changed
- UserExtSources are now updated asynchronously

#### Fixed
- Fix method stringify in StringifyTargetedID.php to be compatible with SimpleSAMLphp 1.18.0+
    - Using getters to get private properties

## [v3.6.0]
#### Added
- Added method getFacilityByEntityId
- Added resource capabilities into entitlements

#### Changed
- Slightly modified text displayed on WAYF
- Updated phpcs ruleset to PSR-12
- is_null() changed to === null
- Using identity comparison instead of equality comparison
- Removed checks in ifs that var is (not) null before empty(var) function (empty checks that itself)
- Double quotes changed to single quotes
- getFacilitiesByEntityId marked as deprecated (getFacilityByEntityId should be used instead)
- Using of getFacilityByEntityId instead of getFacilitiesByEntityId
- Filters JoinGroupsAdnEduPersonEntitlement and PerunGroups merged into PerunEntitlement
- Using expression in asserts (String in assert() is DEPRECATED from PHP 7.2)

#### Fixed
- Fixed wrong dictionary name in post.php
- Removed unnecessary include
- Resolve problem with Sideeffects (PSR1.Files.SideEffects)

## [v3.5.2]
#### Fixed
- Fixed the header on consentform

## [v3.5.1]
#### Fixed
- Fixed bug in filtering IdPs on DS

## [v3.5.0]
#### Changed
- Updated consent page
    - Consent page is shown as a list instead of a teble
    - Changes in dictionary
    - Change the width for keys(col-sm-5) and values(col-sm-7)
- Added filterAttributes option to ProxyFilter for filtering out based on user attribute values

## [v3.4.1]
#### Fixed
- Fixed bugs in disco-tpl.php

## [v3.4.0]
#### Changed
- Remove star which was shown on items on Discovery Service. Now the star will be shown only at previously selected IdP.
- Change work with IdP entities with tags 'social' and  'preferred' on DS
    - Width of entities is now counted automatically
    - Social IdP has 'Sign in with' before name, Preferred IdP hasn't
    - Added possibility to change display name in attribute 'fullDisplayName' in metadata
- If user's last selected IdP is known then show only this IdP and button to show all IdPs 
- Set autofocus on previously selected IdP if exist
- Removed unused function showIcon() in disco-tpl.php

#### Fixed
- Fixed the bug in 'getEntitylesAttribute' function to return correct value of Entityless attribute 
- Fixed the bug in getting new aups to sign

## [v3.3.0]
#### Added
- Added endpoint to get filtered list of metadata in format:
```json
[
  {
    "entityid": "https://entityid1/",
    "name": {
      "en": "IdP1",
      "cs": "IdP1"
    }
  },
  { ... }
]
```
- Added warning types: INFO, WARNING, ERROR

#### Changed
- RpcConnector now stores cookie into file
- Set CONNECTTIMEOUT and TIMEOUT in RpcConnector
- Use new object perunFacility in LDAP to search information about facility
- Configuration for warning on DS is now in module_perun.php

## [v3.2.1]
#### Fixed
- Fixed bug in redirect to registration in case only one VO and one group is available


## [v3.2.0]
#### Added
- Added filter JoinGroupsAndEduPersonEntitlement

#### Changed
- Using of short array syntax (from array() to [])
- Added modes into PerunAttribute process filter
    - MODE_FULL - Rewrite all attributes specified in config
    - MODE_PARTIAL - Rewrite only unset attributes
- Chart.bundle.js is now loaded from SSP module instead of directly from internet

#### Fixed
- Fixed the problem that IDP filter on WAYF didn't work correctly
- Fixed bad error message when the process of bind user to LDAP failed
- Fixed style errors

## [v3.1.1]
#### Fixed
- Added checks into UpdateUserExtSource process filter to prevent undefined index or undefined offset errors 

## [v3.1.0]
#### Added
- PerunAttribute process filter - Added support for numeric attributes

## [v3.0.4]
#### Fixed
- Added missing space before 'addInstitutionButton' or link
- Added missing import
- Fixed the style of changelog
- Fixed the checks in method getMemberStatusByUserAndVo() in AdapterLDAP

## [v3.0.3]
#### Fixed
- Use ldap base from variable in AdapterLdap::getMemberStatusByUserAndVo() instead of static string

## [v3.0.2]
#### Fixed
- Fixed error in case of call method getIdps() with unused tag 

## [v3.0.1]
#### Fixed
- Fixed showing entry on wayf with tag 'preferred'

## [v3.0.0]
#### Added
- Added file phpcs.xml
- Added basic versions of template files

#### Changed
- Changed code standard to PSR-2
- Module uses namespaces
- Changed name of the classes below:
    - sspmod_perun_Auth_Process_ForceAup to SimpleSAML\Module\perun\Auth\Process\ForceAup
    - sspmod_perun_Auth_Process_IdPAttribute to SimpleSAML\Module\perun\Auth\Process\IdpAttribute
    - sspmod_perun_Auth_Process_PerunAttributes to SimpleSAML\Module\perun\Auth\Process\PerunAttributes
    - sspmod_perun_Auth_Process_PerunGroups to SimpleSAML\Module\perun\Auth\Process\PerunGroups
    - sspmod_perun_Auth_Process_PerunIdentity to SimpleSAML\Module\perun\Auth\Process\PerunIdentity
    - sspmod_perun_Auth_Process_ProcessTargetedID to SimpleSAML\Module\perun\Auth\Process\ProcessTargetedID
    - sspmod_perun_Auth_Process_ProxyFilter to SimpleSAML\Module\perun\Auth\Process\ProxyFilter
    - sspmod_perun_Auth_Process_RemoveAllAttributes to SimpleSAML\Module\perun\Auth\Process\RemoveAllAttributes
    - sspmod_perun_Auth_Process_RetainIdPEntityID to SimpleSAML\Module\perun\Auth\Process\RetainIdPEntityID
    - sspmod_perun_Auth_Process_StringifyTargetedID to SimpleSAML\Module\perun\Auth\Process\StringifyTargetedID
    - sspmod_perun_Auth_Process_UpdateUserExtSource to SimpleSAML\Module\perun\Auth\Process\UpdateUserExtSource
    - sspmod_perun_Auth_Process_WarningTestSP to SimpleSAML\Module\perun\Auth\Process\WarningTestSP
    - sspmod_perun_model_Facility to SimpleSAML\Module\perun\model\Facility
    - sspmod_perun_model_Group to SimpleSAML\Module\perun\model\Group
    - sspmod_perun_model_HasId to SimpleSAML\Module\perun\model\HasId
    - sspmod_perun_model_Member to SimpleSAML\Module\perun\model\Member
    - sspmod_perun_model_Resource to SimpleSAML\Module\perun\model\Resource
    - sspmod_perun_model_User to SimpleSAML\Module\perun\model\User
    - sspmod_perun_model_Vo to SimpleSAML\Module\perun\model\Vo
    - sspmod_perun_Adapter to SimpleSAML\Module\perun\Adapter
    - sspmod_perun_AdapterLdap to SimpleSAML\Module\perun\AdapterLdap
    - sspmod_perun_AdapterRpc to SimpleSAML\Module\perun\AdapterRpc
    - DatabaseCommand to SimpleSAML\Module\perun\DatabaseCommand
    - DatabaseConnector to SimpleSAML\Module\perun\DatabaseConnector
    - sspmod_perun_Disco to SimpleSAML\Module\perun\Disco
    - sspmod_perun_DiscoTemplate to SimpleSAML\Module\perun\DiscoTemplate
    - sspmod_perun_Exception to SimpleSAML\Module\perun\Exception
    - sspmod_perun_IdpListsService to SimpleSAML\Module\perun\IdpListsService
    - sspmod_perun_IdpListsServiceCsv to SimpleSAML\Module\perun\IdpListsServiceCsv
    - sspmod_perun_IdpListsServiceDB to SimpleSAML\Module\perun\IdpListsServiceDB
    - sspmod_perun_LdapConnector to SimpleSAML\Module\perun\LdapConnector
    - sspmod_perun_RpcConnector to SimpleSAML\Module\perun\RpcConnector
- Added disco-tpl template file
- Method getUsersGroupsOnFacility in AdapterRpc was optimized
- Searching of institutions on WAYF is accent-insensitive
- Changed config file for listOfSps

#### Fixed
- Fixed the bug generating Array to string conversion Exception in PerunAttributes, 
when storing one Perun attribute to more SAML attribute 

#### Removed
- Removed template config file module_perun_listOfSps.php 
(Configuration of listOfSps.php page is moved to module_perun.php)

## [v2.2.0]

#### Added
- List of services is displayed as JSON if parameter 'output=json' is set in URL
- Page showing status of selected components
   - This page is also available in JSON format if parameter 'output=json' is set in URL

#### Changed
- Updated composer.json dependencies

#### Fixed
- Fixed the problem where LDAP calls RPC method in PerunIdentity filter
- Fixed assignation of one Perun attribute to multiple SP attributes

## [v2.1.0]
#### Added
- Added new atribute in PerunIdentity process filter with list of Services identifier's for which we don't want to show page with information, that the user will be redirected to other page 

#### Changed
- Changed design of ListOfSps
- Changed the texts and visual form of pages: perun_identity_choose_vo_and_group.php and unauthorized_access_go_to_registration.php

#### Fixed
- Fixed resend SPMetadata from request to unauthorized-access-go-to-registration page
- Fixed url encoding in PerunGroups

## [v2.0.0]
#### Added
- Added badges to README
- Added page with configurable table of SPs on Proxy
- Added new model Member
- Added new model Resource
- New methods for getting data from Perun LDAP and Perun RPC
- Added function for generating metadata for SimpleSAMLphp Proxy AAI from Perun
- Added UpdateUserExtSource filter

#### Changed
- Connectors methods are not static for now.
- Added constructors to Adapters, which allows specified config file for each connections.
- New properties voId and uniqueName in Group model
- Function getSpGroup require only one param($spEntityId)
- Function unauthorize in PerunIdentity is now public
- Changed the login and registration process

#### Fixed
- Fixed the problem with access to non-secured LDAP
- Fixed the bad call of function 'searchForEntity(...)' in function getVoById() in AdapterLdap.php  

## [v1.0.0]

[Unreleased]: https://github.com/CESNET/perun-simplesamlphp-module/tree/master
[v6.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v6.0.0
[v5.2.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v5.2.0
[v5.1.1]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v5.1.1
[v5.1.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v5.1.0
[v5.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v5.0.0
[v4.1.1]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v4.1.1
[v4.1.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v4.1.0
[v4.0.4]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v4.0.4
[v4.0.3]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v4.0.3
[v4.0.2]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v4.0.2
[v4.0.1]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v4.0.1
[v4.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v4.0.0
[v3.9.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.9.0
[v3.8.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.8.0
[v3.7.4]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.7.4
[v3.7.3]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.7.3
[v3.7.2]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.7.2
[v3.7.1]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.7.1
[v3.7.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.7.0
[v3.6.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.6.0
[v3.5.2]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.5.2
[v3.5.1]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.5.1
[v3.5.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.5.0
[v3.4.1]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.4.1
[v3.4.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.4.0
[v3.3.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.3.0
[v3.2.1]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.2.1
[v3.2.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.2.0
[v3.1.1]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.1.1
[v3.1.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.1.0
[v3.0.4]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.0.4
[v3.0.3]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.0.3
[v3.0.2]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.0.2
[v3.0.1]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.0.1
[v3.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v3.0.0
[v2.2.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v2.2.0
[v2.1.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v2.1.0
[v2.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v2.0.0
[v1.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v1.0.0
