<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\Auth\Process;

use SAML2\XML\saml\NameID;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Error\Exception;

/**
 * Filter checks whether UID attribute is object of type DOMNodeList. If yes, then it supposes it is derived form XML
 * <saml2:NameID NameQualifier="https://idp" SPNameQualifier="https://sp">uid</saml2:NameID> which converts to
 * [NameQualifier]![SPNameQualifier]![TextValue] resp. https://idp!https://sp!uid If configuration option
 * targetAttribute is provided, uid attribute stays unchanged and new attribute is filled. If no, uid attribute is
 * overwritten.
 *
 * @author Michal Prochazka <michalp@ics.muni.cz>
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */

class StringifyTargetedID extends ProcessingFilter
{
    private $uidAttr;

    private $targetAttr;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert(is_array($config));

        if (! isset($config['uidAttr'])) {
            throw new Exception('perun:ProcessTargetedID: missing mandatory configuration option \'uidAttr\'.');
        }
        if (! isset($config['targetAttr'])) {
            $config['targetAttr'] = $config['uidAttr'];
        }

        $this->uidAttr = (string) $config['uidAttr'];
        $this->targetAttr = (string) $config['targetAttr'];
    }

    public function process(&$request)
    {
        assert(is_array($request));

        if (! empty($request['Attributes'][$this->uidAttr])) {
            $stringified = $this->stringify($request['Attributes'][$this->uidAttr][0]);
            $request['Attributes'][$this->targetAttr] = [$stringified];
        }
    }

    /**
     * Convert NameID value into the text representation.
     *
     * @return NameID|string
     */
    private function stringify(NameID $attributeValue)
    {
        if (is_object($attributeValue) && get_class($attributeValue) === 'SAML2\XML\saml\NameID') {
            return $attributeValue->getNameQualifier() . '!' . $attributeValue->getSPNameQualifier() . '!'
                . $attributeValue->getValue();
        }
        return $attributeValue;
    }
}
