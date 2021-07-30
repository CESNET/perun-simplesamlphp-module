<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

class LoginInfo extends ProcessingFilter
{
    public const ATTRS = 'attrs';

    public const SOURCE_IDP_ATTR = 'sourceIdPAttr';

    public const SOURCE_IDENTIFIER_ATTR = 'sourceIdentifierAttr';

    private $attrs;

    private $sourceIdpAttr;

    private $sourceIdentifierAttr;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->attrs = $config->getArray(self::ATTRS);
        $this->sourceIdpAttr = $config->getString(self::SOURCE_IDP_ATTR, '');
        $this->sourceIdentifierAttr = $config->getString(self::SOURCE_IDENTIFIER_ATTR, '');
    }

    public function process(&$request)
    {
        $spEntityId = $request['SPMetadata']['entityid'];

        $finalString = 'User ';

        if (isset($request['perun']['user'])) {
            $user = $request['perun']['user'];
            $finalString .= 'ID: ' . $user->getId() . ', ';
        }

        if (! empty($this->attrs)) {
            $finalString .= 'identifiers: [';

            foreach ($this->attrs as $attr) {
                if (isset($request['Attributes'][$attr][0])) {
                    $finalString .= $attr . ': ' . $request['Attributes'][$attr][0] . ', ';
                }
            }

            $finalString = substr($finalString, 0, -2) . '], ';
        }

        $finalString .= 'service: ' . $spEntityId;

        if (! empty($this->sourceIdentifierAttr) && isset($request['Attributes'][$this->sourceIdentifierAttr][0])) {
            $finalString .= ', external identity: ' . $request['Attributes'][$this->sourceIdentifierAttr][0];
            if (! empty($this->sourceIdpAttr) && isset($request['Attributes'][$this->sourceIdpAttr][0])) {
                $finalString .= ' from ' . $request['Attributes'][$this->sourceIdpAttr][0];
            }
        }

        Logger::notice($finalString);
    }
}
