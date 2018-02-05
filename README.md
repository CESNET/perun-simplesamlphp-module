# perun-simplesamlphp-module
Module which allows simpleSAMLphp to get data from Perun.


Once you have installed SimpleSAMLphp, installing this module is very simple. First of all, you will need to download Composer if you haven't already. After installing Composer, just execute the following command in the root of your SimpleSAMLphp installation:

## Instalation

1.Install necessary packages

* Install module Perunauthorize 

  * visit https://github.com/CESNET/perunauthorize-simplesamlphp-module

2.Add follows repository to composer.json

```json
    "repositories":[
        {
                "type": "git",
                "url": "https://github.com/CESNET/perunauthorize-simplesamlphp-module.git"
        },
        {
                "type": "git",
                "url": "https://github.com/elixirhub/elixir-aai-proxy-idp-template.git"
        },
        {
                "type": "git",
                "url": "https://github.com/ICS-MU/ceitec-aai-proxy-idp-template.git"
        },
        {
                "type": "git",
                "url": "https://github.com/CESNET/einfra-aai-proxy-idp-template.git"
        },
        {
                "type": "git",
                "url": "https://github.com/CESNET/bbmri-aai-proxy-idp-template.git"
        },
        {
                "type": "git",
                "url": "https://github.com/CESNET/simplesamlphp-authgoogleoauth2.git"
        },
        {
                "type": "git",
                "url": "https://github.com/rciam/simplesamlphp-module-authorcid.git"
        },
        {
                "type": "git",
                "url": "https://github.com/CESNET/proxystatistics-simplesamlphp-module.git"
        },
        {
                "type": "git",
                "url": "https://github.com/CESNET/perun-simplesamlphp-module.git"
        }
    ]
```

3.Install module

`php composer.phar require cesnet/simplesamlphp-module-perun:dev-EPTID_hack`