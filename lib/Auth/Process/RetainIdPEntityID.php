<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * Class sspmod_perun_Auth_Process_RetainIdPEntityID
 *
 * Filter extract entityID of source remote (source/original) IdP to attribute defined by 'attrName' config property. It
 * supposed to be used in proxy SP context. Means it should be defined in authsources or idp-remote files. But it can be
 * placed also in IdP context. In such case it extracts this hosted IdP entityID.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class RetainIdPEntityID extends \SimpleSAML\Auth\ProcessingFilter
{
    public const DEFAULT_ATTR_NAME = 'sourceIdPEntityID';

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
        assert(is_array($request));

        if (isset($request['Source']['entityid'])) {
            $entityId = $request['Source']['entityid'];
        } else {
            throw new Exception('perun:RetainIdPEntityID: Cannot find entityID of remote IDP. ' .
                'hint: Do you have this filter in SP context?');
        }

        $request['Attributes'][$this->attrName] = [$entityId];
        Logger::debug(
            'perun:RetainIdPEntityID: entityID \'' . $entityId . '\' was extracted to attribute ' . $this->attrName
        );
    }
}
