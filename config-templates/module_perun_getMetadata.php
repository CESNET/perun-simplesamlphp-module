<?php

$config = [
    /**
     * Identifier of Proxy
     */
    'proxyIdentifier' => '',

    /**
     * Name of facility attribute Proxy Identifiers
     */
    'perunProxyIdentifierAttr' => '',

    /**
     * Name of facility attribute EntityID
     */
    'perunProxyEntityIDAttr' => '',

    /**
     * Absolute path, where the metadata will be stored
     */
    'absoluteFileName' => '',

    /**
     * List of attributes definitions
     */
    'attributesDefinitions' => [
        // Name of attribute from perun => key which will be used in generated metadata
        'perunAttrName' => 'metadataName',
    ],
];
