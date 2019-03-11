# Change Log
All notable changes to this project will be documented in this file.
 
 ## [Unreleased]
 [Added]
 - Added file phpcs.xml
 
 ## [v2.2.0]
 [Added]
 - List of services is displayed as JSON if parameter 'output=json' is set in URL
 - Page showing status of selected components
    - This page is also available in JSON format if parameter 'output=json' is set in URL

 [Changed]
 - Updated composer.json dependencies

 [Fixed]
 - Fixed the problem where LDAP calls RPC method in PerunIdentity filter
 - Fixed assignation of one Perun attribute to multiple SP attributes
 
 ## [v2.1.0]
 [Added]
 - Added new atribute in PerunIdentity process filter with list of Services identifier's for which we don't want to show page with information, that the user will be redirected to other page 
 
 [Changed]
 - Changed design of ListOfSps
 - Changed the texts and visual form of pages: perun_identity_choose_vo_and_group.php and unauthorized_access_go_to_registration.php

 [Fixed]
 - Fixed resend SPMetadata from request to unauthorized-access-go-to-registration page
 - Fixed url encoding in PerunGroups
 
 ## [v2.0.0]
 [Added]
 - Added badges to README
 - Added page with configurable table of SPs on Proxy
 - Added new model Member
 - Added new model Resource
 - New methods for getting data from Perun LDAP and Perun RPC
 - Added function for generating metadata for SimpleSAMLphp Proxy AAI from Perun
 - Added UpdateUserExtSource filter
 
 [Changed]
 - Connectors methods are not static for now.
 - Added constructors to Adapters, which allows specified config file for each connections.
 - New properties voId and uniqueName in Group model
 - Function getSpGroup require only one param($spEntityId)
 - Function unauthorize in PerunIdentity is now public
 - Changed the login and registration process
 
 [Fixed]
 - Fixed the problem with access to non-secured LDAP
 - Fixed the bad call of function 'searchForEntity(...)' in function getVoById() in AdapterLdap.php  
 
 ## [v1.0.0]

 [Unreleased]: https://github.com/CESNET/perun-simplesamlphp-module/tree/master
 [v2.2.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v2.2.0
 [v2.1.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v2.1.0
 [v2.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v2.0.0
 [v1.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v1.0.0
