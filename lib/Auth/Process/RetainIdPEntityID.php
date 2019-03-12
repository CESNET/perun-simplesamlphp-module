<?php

/**
 * Class sspmod_perun_Auth_Process_RetainIdPEntityID
 *
 * Filter extract entityID of source remote (source/original) IdP to attribute defined by 'attrName' config property.
 * It supposed to be used in proxy SP context. Means it should be defined in authsources or idp-remote files.
 * But it can be placed also in IdP context. In such case it extracts this hosted IdP entityID.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_Auth_Process_RetainIdPEntityID extends SimpleSAML_Auth_ProcessingFilter
{
    const DEFAULT_ATTR_NAME = 'sourceIdPEntityID';

    private $attrName;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        # Target attribute can be set in config, if not, the the default is used
        if (isset($config['attrName'])) {
            $this->attrName = $config['attrName'];
        } else {
            $this->attrName = self::DEFAULT_ATTR_NAME;
        }
    }


    public function process(&$request)
    {
        assert('is_array($request)');

        if (isset($request['Source']['entityid'])) {
            $entityId = $request['Source']['entityid'];
        } else {
            throw new SimpleSAML_Error_Exception("perun:RetainIdPEntityID: Cannot find entityID of remote IDP. " .
                "hint: Do you have this filter in SP context?");
        }

        $request['Attributes'][$this->attrName] = array($entityId);
        SimpleSAML\Logger::debug(
            "perun:RetainIdPEntityID: entityID '$entityId' was extracted to attribute " . $this->attrName
        );
    }
}
