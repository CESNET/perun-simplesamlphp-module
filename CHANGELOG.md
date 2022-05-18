## [7.11.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.11.0...v7.11.1) (2022-05-18)


### Bug Fixes

* 🐛 Fix using approvalUrl where perunApprovalUrl should be u ([66e13ee](https://github.com/CESNET/perun-simplesamlphp-module/commit/66e13ee91da208c86690b67bd4a178909dc4f5b8))

# [7.11.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.10.1...v7.11.0) (2022-04-29)


### Features

* 🎸 Possibility to hide authN protocol, small fixes ([635ea64](https://github.com/CESNET/perun-simplesamlphp-module/commit/635ea641f5afa315dea6f1d11a504db769f33fc9))

## [7.10.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.10.0...v7.10.1) (2022-04-22)


### Bug Fixes

* 🐛 Fixed PrivacyIDEA template ([66b6656](https://github.com/CESNET/perun-simplesamlphp-module/commit/66b6656823ca2bc1c16349b1659c89574c7d7523))

# [7.10.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.9.0...v7.10.0) (2022-04-22)


### Features

* 🎸 Additional identifiers lookup ([36f7f7c](https://github.com/CESNET/perun-simplesamlphp-module/commit/36f7f7ceddeecb9ae532090f702f379b01c8c807))

# [7.9.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.8.2...v7.9.0) (2022-04-14)


### Features

* **forceaup:** new option entityID, fix required checks ([e2ec315](https://github.com/CESNET/perun-simplesamlphp-module/commit/e2ec3155db7d727c4c23c84471d3dc7ca68449c6))

## [7.8.2](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.8.1...v7.8.2) (2022-04-14)


### Bug Fixes

* Swaps getUsersGroupsOnSp and getUsersGroupsOnFacility methods ([660ba85](https://github.com/CESNET/perun-simplesamlphp-module/commit/660ba85369725e490ca1bf9b19757d748cbb61c4))

## [7.8.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.8.0...v7.8.1) (2022-04-13)


### Bug Fixes

* 🐛 Fix direct registration in SpAuthorization ([1e52a49](https://github.com/CESNET/perun-simplesamlphp-module/commit/1e52a49bbc2c0eccdbafb34d9b3ef0ac1751e33d))

# [7.8.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.7.0...v7.8.0) (2022-04-13)


### Bug Fixes

* Code checks ([fca9739](https://github.com/CESNET/perun-simplesamlphp-module/commit/fca9739d39ebb41d8cf38de257aacbc15a552c47))
* Minor fixes in AuthProc filters ([48c6949](https://github.com/CESNET/perun-simplesamlphp-module/commit/48c6949edc155aa722eb50bc87cd9136c8c0ee61))
* PerunEnsureMember sends users which are not in vo to regitration ([524c6ed](https://github.com/CESNET/perun-simplesamlphp-module/commit/524c6eddcb11c7c2cb4a210cb61d18eb01b15a72))
* Removes redundant log in updateUes script ([232d3b8](https://github.com/CESNET/perun-simplesamlphp-module/commit/232d3b82d03841e13c127a30e56cfa4d6b5b7b36))
* Rewrites aarc_idp_hint ([9657f72](https://github.com/CESNET/perun-simplesamlphp-module/commit/9657f72b5a2216ba240bc00382f992a22c211cbc))
* SpAuthorization - unouthorized when user is not in the request ([f201a15](https://github.com/CESNET/perun-simplesamlphp-module/commit/f201a15c135b6fccea77b611c7d453de443bde28))
* store a full attribute object from RPC ([efc0f8f](https://github.com/CESNET/perun-simplesamlphp-module/commit/efc0f8fce3ee77002f844191ad4ae64ea83484cb))
* Updates processFilterConfigurations-example ([760b6bd](https://github.com/CESNET/perun-simplesamlphp-module/commit/760b6bdc2905f122cce9a9a787dd1d3e9f321aba))
* updateUes - attr initialization from null to [] ([294f7c4](https://github.com/CESNET/perun-simplesamlphp-module/commit/294f7c4c8fa69f3b4fd804027ad1960847afdc9c))


### Features

* Adapter - getUsersGroupsOnSp, getGroupsWhereMemberIsActive ([18b6aed](https://github.com/CESNET/perun-simplesamlphp-module/commit/18b6aed3e0a1f523b54b97503c7ccb9391ba3fe2))
* PerunConstants ([520bbb7](https://github.com/CESNET/perun-simplesamlphp-module/commit/520bbb70cfde28ec930abdaf553f96be2300f0dd))
* PerunEnsureMember ([373d3a3](https://github.com/CESNET/perun-simplesamlphp-module/commit/373d3a3beda22bc9064c92f81498704d7dbeecc6))
* PerunUserGroups ([48fd82c](https://github.com/CESNET/perun-simplesamlphp-module/commit/48fd82c0c15faedc3390f4dbb969702c070bb019))
* SpAuthorization - adds handle_unsatisfied_membership option ([13ca45e](https://github.com/CESNET/perun-simplesamlphp-module/commit/13ca45e3faaf53ad089cd9db76c7d06c598745eb))
* UpdateUserExtSource - introduces appendOnlyAttrs, fixes the way how attrsToUpdate are created ([b241135](https://github.com/CESNET/perun-simplesamlphp-module/commit/b241135de6fbcc526213b7334f3480291850f358))

# [7.7.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.6.4...v7.7.0) (2022-04-11)


### Features

* ContactsToArray transformer ([015fb7f](https://github.com/CESNET/perun-simplesamlphp-module/commit/015fb7f8672cc6982e674406724d43f7e44c4ea2))

## [7.6.4](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.6.3...v7.6.4) (2022-04-06)


### Bug Fixes

* Filters ([96a75de](https://github.com/CESNET/perun-simplesamlphp-module/commit/96a75dea431e2cdd287462bd7dc76ac97e537f92))

## [7.6.3](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.6.2...v7.6.3) (2022-04-06)


### Bug Fixes

* 🐛 Fix reading configurati novalues in ExtractRequestAttrib ([60d2ffb](https://github.com/CESNET/perun-simplesamlphp-module/commit/60d2ffb01228208fa035267cbd8cbf94aa6839fa))
* 🐛 Small fix in redirects in the PerunUser filter ([e0166f6](https://github.com/CESNET/perun-simplesamlphp-module/commit/e0166f6e21e6a6c299d8da1f9e48d91bf169ee04))

## [7.6.2](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.6.1...v7.6.2) (2022-04-05)


### Bug Fixes

* 🐛 Fix JSON in perun dictionary ([41bf728](https://github.com/CESNET/perun-simplesamlphp-module/commit/41bf7281a5e1af734f0ad5edba260d988bfa4438))

## [7.6.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.6.0...v7.6.1) (2022-04-04)


### Bug Fixes

* 🐛 Fix default value in ForceAup due to strictypes ([eb75544](https://github.com/CESNET/perun-simplesamlphp-module/commit/eb75544958bf6feee5bbd644b7aa7b872a21402b))

# [7.6.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.5.1...v7.6.0) (2022-04-04)


### Bug Fixes

* 🐛 Fix code style ([77729ea](https://github.com/CESNET/perun-simplesamlphp-module/commit/77729ea0fc11eb37f7c2576d2866bfb79f372b62))


### Features

* 🎸 AuthProcFilter GenerateIdPAttributes ([a2ca6ea](https://github.com/CESNET/perun-simplesamlphp-module/commit/a2ca6ea455bbcd281d3d9fefb564e39c32f5e7e0))
* 🎸 AuthProcFilter PerunUser - identify user from Perun ([b31976a](https://github.com/CESNET/perun-simplesamlphp-module/commit/b31976a4ace2fd0d592632e83131d453e3a6b103))
* 🎸 AuthProcFilter QualifyNameID ([1f8bd75](https://github.com/CESNET/perun-simplesamlphp-module/commit/1f8bd750bb6013d56ea4332c50b38560aa955fcd))
* 🎸 DropUserAttributes authProcFilter ([c763ad9](https://github.com/CESNET/perun-simplesamlphp-module/commit/c763ad9e97cc2fd6b191f7b95f096ce6a042255f))
* 🎸 New filter for extracting attribute from request var ([6c6110f](https://github.com/CESNET/perun-simplesamlphp-module/commit/6c6110fd0ab2eee62ee68a0e819fbb94bf2b185e))
* 🎸 PerunAup authProcFilter ([301139a](https://github.com/CESNET/perun-simplesamlphp-module/commit/301139a4c72357e65dfa1b5f2423c179fb080d75))
* 🎸 SpAuthorization authproc filter ([5771a1b](https://github.com/CESNET/perun-simplesamlphp-module/commit/5771a1b3cf04db4805f26cee3a0934ddb2399fe1))
* Consolidator app ([e7bbde9](https://github.com/CESNET/perun-simplesamlphp-module/commit/e7bbde9a85f8c0d67a16e6987a7614bbc9bb4995))

## [7.5.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.5.0...v7.5.1) (2022-04-01)


### Bug Fixes

* getPerunUser name construction ([ec7150a](https://github.com/CESNET/perun-simplesamlphp-module/commit/ec7150a8cd75b40f57e808cde1108fd7425c249e))

# [7.5.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.4.0...v7.5.0) (2022-03-30)


### Features

* updateUes - configurable identifiers ([2a3d052](https://github.com/CESNET/perun-simplesamlphp-module/commit/2a3d052b65e49ceb1f6128f29ed58465d2d3da8d))

# [7.4.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.3.0...v7.4.0) (2022-03-29)


### Features

* Do not show previous selection for SPs listed in config ([dda8140](https://github.com/CESNET/perun-simplesamlphp-module/commit/dda81406b58f92eb472fd6226adcde2ef6612349))

# [7.3.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.2.1...v7.3.0) (2022-03-18)


### Features

* Custom AttributeMap filter ([903bd6f](https://github.com/CESNET/perun-simplesamlphp-module/commit/903bd6fc3f6a349c2ed359bc34edcd6f3cf72858))

## [7.2.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.2.0...v7.2.1) (2022-03-11)


### Bug Fixes

* 🐛 Fix privacyIDEA form for new version of PI module ([9a67d39](https://github.com/CESNET/perun-simplesamlphp-module/commit/9a67d39cf3e9e81077b3b62630434bde296f8db3))

# [7.2.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.1.1...v7.2.0) (2022-03-09)


### Features

* Custom privacyIDEA login template ([15359e0](https://github.com/CESNET/perun-simplesamlphp-module/commit/15359e0d03b60d6ffbba76f5e0dc799785151ac2))

## [7.1.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.1.0...v7.1.1) (2022-03-07)


### Bug Fixes

* Fixed AUP filter ([9ecf4c0](https://github.com/CESNET/perun-simplesamlphp-module/commit/9ecf4c01eb2ec3e823b69f16ffadd389b4760f20))

# [7.1.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.0.3...v7.1.0) (2022-01-13)


### Features

* 🎸 Added RestoreAcrs authproc filter, modify ACRs when MFA ([ebafb05](https://github.com/CESNET/perun-simplesamlphp-module/commit/ebafb059323b8808fb0c04009e61d9ab5fa123b5))

## [7.0.3](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.0.2...v7.0.3) (2022-01-11)


### Bug Fixes

* refactor disco ([#218](https://github.com/CESNET/perun-simplesamlphp-module/issues/218)) ([31f8216](https://github.com/CESNET/perun-simplesamlphp-module/commit/31f82168cf0f294c43490e7a79e102542e582530))

## [7.0.2](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.0.1...v7.0.2) (2022-01-11)


### Bug Fixes

* **forceaup:** drop unused option uidAttr ([#215](https://github.com/CESNET/perun-simplesamlphp-module/issues/215)) ([a18fad6](https://github.com/CESNET/perun-simplesamlphp-module/commit/a18fad6da7ea41a154eb4b497d5f5a7a503c9cad)), closes [#157](https://github.com/CESNET/perun-simplesamlphp-module/issues/157)

## [7.0.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v7.0.0...v7.0.1) (2022-01-05)


### Bug Fixes

* Fixed some unchecked potential errors ([#204](https://github.com/CESNET/perun-simplesamlphp-module/issues/204)) ([617153c](https://github.com/CESNET/perun-simplesamlphp-module/commit/617153c41cae8c187ffa2c529d76c5966bfe25c6))

# [7.0.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.4.3...v7.0.0) (2022-01-05)


### chore

* add missing dependencies, PHP >= 7.1, SSP 1.19, add package-lock ([6c873af](https://github.com/CESNET/perun-simplesamlphp-module/commit/6c873af0533c20acfde90399bf5836ef67a9a299))


### BREAKING CHANGES

* PHP 7.1 or higher is required, SSP 1.19 is required

## [6.4.3](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.4.2...v6.4.3) (2021-12-13)


### Bug Fixes

* typo in RpcConnector ([4e15e8b](https://github.com/CESNET/perun-simplesamlphp-module/commit/4e15e8ba350db256b39417a307e0db3a8a8c1c13))

## [6.4.2](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.4.1...v6.4.2) (2021-11-25)


### Bug Fixes

* make database required for challenges, skip challenge cleanup without database ([c42c3fa](https://github.com/CESNET/perun-simplesamlphp-module/commit/c42c3fa2a3be98509d0d3e9037fff2a0722a5db1)), closes [#182](https://github.com/CESNET/perun-simplesamlphp-module/issues/182)

## [6.4.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.4.0...v6.4.1) (2021-11-24)


### Bug Fixes

* 🐛 Fix wrong variable names in getFacilityByXY methods ([986a7d8](https://github.com/CESNET/perun-simplesamlphp-module/commit/986a7d85de7fbfd3e63f4a7587bb80e473252376))

# [6.4.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.3.3...v6.4.0) (2021-11-24)


### Features

* Added possibility to add a service name on WAYF ([1c84441](https://github.com/CESNET/perun-simplesamlphp-module/commit/1c844417dd241ac2255713387b2b2052760a70e8))

## [6.3.3](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.3.2...v6.3.3) (2021-11-16)


### Bug Fixes

* 🐛 Remove fixed footer for warning_test_sp ([540afac](https://github.com/CESNET/perun-simplesamlphp-module/commit/540afac23a60916d1676998bb96ba18b5720676d))

## [6.3.2](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.3.1...v6.3.2) (2021-11-15)


### Bug Fixes

* prevent type errors in RPC connector ([5152cbe](https://github.com/CESNET/perun-simplesamlphp-module/commit/5152cbe154f950c1a83b25c7e8c6e8d68051bf12))

## [6.3.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.3.0...v6.3.1) (2021-11-03)


### Bug Fixes

* 🐛 Added missing ext-intl to the composer.json ([e79bd2a](https://github.com/CESNET/perun-simplesamlphp-module/commit/e79bd2a59547e4ccfd674a4f962fad285c17c2ac))

# [6.3.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.2.0...v6.3.0) (2021-10-12)


### Bug Fixes

* 🐛 Add check of key existence in template - unauth-acc-reg ([34c10d5](https://github.com/CESNET/perun-simplesamlphp-module/commit/34c10d52df8afcac2f07c4ee4d3fb13401c5f8bc))


### Features

* Turn off addInstitution when whitelisting is disabled ([91990b5](https://github.com/CESNET/perun-simplesamlphp-module/commit/91990b55d94766cff330a6e686bb5b4fe7ec45a2))

# [6.2.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.1.1...v6.2.0) (2021-10-12)


### Features

* Added support for old browsers ([4d62561](https://github.com/CESNET/perun-simplesamlphp-module/commit/4d62561cf8ca7ba2bb3645027541f0bc11c34681))

## [6.1.1](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.1.0...v6.1.1) (2021-09-29)


### Bug Fixes

* Changed text labels on consent ([1764572](https://github.com/CESNET/perun-simplesamlphp-module/commit/1764572365e015291c35ac7ae03679bd9000d52e))

# [6.1.0](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.0.7...v6.1.0) (2021-09-21)


### Bug Fixes

* Fixed ECS bugs ([0ac5a9f](https://github.com/CESNET/perun-simplesamlphp-module/commit/0ac5a9f910c1496d46d4e398ece4306b03bce868))


### Features

* Added metadata expiration page ([e1ad062](https://github.com/CESNET/perun-simplesamlphp-module/commit/e1ad062c7747be6ff2fb68ccbd8e01d43318a904))

## [6.0.7](https://github.com/CESNET/perun-simplesamlphp-module/compare/v6.0.6...v6.0.7) (2021-09-10)


### Bug Fixes

* bugfixes in list of SPs ([1cd84a8](https://github.com/CESNET/perun-simplesamlphp-module/commit/1cd84a8bdbe879bdec568b1952b8756f29d99f94))

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
