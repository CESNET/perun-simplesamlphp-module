# Change Log
All notable changes to this project will be documented in this file.
 
 ## [Unreleased]
 [Changed]
 - Changed design of ListOfSps
 
 [Fixed]
 - Fixed resend SPMetadata from request to unauthorized-access-go-to-registration page
 
 ## [v2.0.0]
 [Added]
 - Added badges to README
 - Added page with configurable table of SPs on Proxy
 - Added new model Member
 - Added new model Resource
 - New methods for getting data from Perun LDAP and Perun RPC
 - Added function for generating metadata for SimpleSAMLphp Proxy AAI from Perun
 
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
 [v2.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v2.0.0
 [v1.0.0]: https://github.com/CESNET/perun-simplesamlphp-module/tree/v1.0.0
