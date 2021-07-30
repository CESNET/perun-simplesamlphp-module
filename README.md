# perun-simplesamlphp-module
[![Latest Stable Version](https://poser.pugx.org/cesnet/simplesamlphp-module-perun/v/stable)](https://packagist.org/packages/cesnet/simplesamlphp-module-perun)
[![Latest Unstable Version](https://poser.pugx.org/cesnet/simplesamlphp-module-perun/v/unstable)](https://packagist.org/packages/cesnet/simplesamlphp-module-perun)
[![CI](https://github.com/CESNET/perun-simplesamlphp-module/actions/workflows/build_and_check.yml/badge.svg)](https://github.com/CESNET/perun-simplesamlphp-module/actions/workflows/build_and_check.yml)
[![License](https://poser.pugx.org/cesnet/simplesamlphp-module-perun/license)](https://packagist.org/packages/cesnet/simplesamlphp-module-perun)

Module which allows simpleSAMLphp to get data from Perun.

## Contribution

This repository uses [Conventional Commits](https://www.npmjs.com/package/@commitlint/config-conventional).

Any change that significantly changes behavior in a backward-incompatible way or requires a configuration change must be marked as BREAKING CHANGE.

### Available scopes:
* theme
* Auth Process filters:
    * ensurevomember
    * forceaup
    * idpattribute
    * logininfo
    * perunattributes
    * entitlement
    * perunidentity
    * targetedid
    * proxyfilter
    * removeallattributes
    * idpentityid
    * stringifytargetedid
    * updateues
    * warningtestsp
* ...


## Instalation

Once you have installed SimpleSAMLphp, installing this module is very simple. First, you will need to download Composer if you haven't already. After installing Composer, just execute the following command in the root of your SimpleSAMLphp installation:

`php composer.phar require cesnet/simplesamlphp-module-perun:dev-master`