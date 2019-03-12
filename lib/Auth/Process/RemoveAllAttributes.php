<?php

/**
 * Filter simply remove all attributes from requests. It is meant to use because We do not want to pass
 * any attributes directly from IdP. Rather fetch all from Perun.
 * Because the attributes should not depends on IdP which user currently used.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class sspmod_perun_Auth_Process_RemoveAllAttributes extends SimpleSAML_Auth_ProcessingFilter
{

    const FILTER_ATTRIBUTE_LIST = "filterAttributeList";

    private $filterAttributeList = array();

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (isset($config[self::FILTER_ATTRIBUTE_LIST])) {
            $this->filterAttributeList = $config[self::FILTER_ATTRIBUTE_LIST];
        }
    }

    public function process(&$request)
    {
        assert('is_array($request)');

        $attributes = array();

        foreach ($request['Attributes'] as $attributeKey => $attributeValue) {
            if (in_array($attributeKey, $this->filterAttributeList)) {
                $attributes[$attributeKey] = $attributeValue;
            }
        }

        $request['Attributes'] = array();

        foreach ($attributes as $attributeKey => $attributeValue) {
            $request['Attributes'][$attributeKey] = $attributeValue;
        }
    }
}
