# Change Log
All notable changes to this project will be documented in this file.
 
## [Unreleased]
#### Fixed
- Fixed the problem that IDP filter on WAYF didn't work correctly

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
