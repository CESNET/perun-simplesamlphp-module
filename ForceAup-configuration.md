Configuration of ForceAup 
-
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

- Add this configuration into metadata file saml20-idp-hosted.php
    
    ```
    40 => array(
        'class' => 'perun:ProxyFilter',
        'filterSPs' => $perunEntityIds,
        'config' => array(
            'class' => 'perun:ForceAup',
            'uidAttr' => 'uid',
            'interface' => 'rpc',
            'perunAupsAttr' => 'urn:perun:entityless:attribute-def:def:orgAups',
            'perunUserAupAttr' => 'urn:perun:user:attribute-def:def:aups',
            'perunVoAupAttr' => 'urn:perun:vo:attribute-def:def:aup',
            'perunFacilityReqAupsAttr' => 'urn:perun:facility:attribute-def:def:reqAups',
            'facilityVoShortNames' => 'urn:perun:facility:attribute-def:virt:voShortNames'
        ),
    ),   
    ``` 

3.Fill the attributes and set list of required Aups (attr reqAups) and voShortNames (optional) for each facility
