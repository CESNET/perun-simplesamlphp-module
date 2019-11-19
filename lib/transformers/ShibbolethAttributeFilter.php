<?php

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

class ShibbolethAttributeFilter implements AttributeTransformer
{
    const DENIED_ATTRIBUTE_PREFIX = '-';

    private $ignoreAttributes;

    private $ignoreEntityIds;

    private $entityCategories;

    private $entityIdAttribute;

    private $attributesAttribute;

    private $entityCategoriesAttribute;

    private $tagsAttribute;

    private $skipDefault;

    private $releasedAttributes = [];

    private $releasedAttributesForAll = [];

    private $tags = [];

    public function __construct($config)
    {
        $this->ignoreAttributes = $config['ignore.attributes'] ?? [];
        $this->ignoreEntityIds = $config['ignore.entityIDs'] ?? [];
        $this->entityCategories = $config['entityCategories'] ?? [];
        $this->entityIdAttribute = \SimpleSAML\Module\perun\MetadataToPerun::ENTITY_ID;
        $this->attributesAttribute = $config['attributesAttribute'] ?? 'requiredAttributes';
        $this->entityCategoriesAttribute = $config['entityCategoriesAttribute'] ?? 'entityCategory';
        $this->tagsAttribute = $config['tagsAttribute'] ?? null;
        $this->skipDefault = !empty($config['skipDefault']);

        $data = $config['file'] ?? $config['xml'];
        $data_is_url = !empty($config['file']);
        $this->parseAttributeFilter($data, $data_is_url);
    }

    public function transform($attributes)
    {
        if (empty($attributes[$this->entityIdAttribute])) {
            throw new \Exception('entityId is missing');
        }
        $entityId = $attributes[$this->entityIdAttribute];
        $entityCategories = $attributes[$this->entityCategoriesAttribute] ?? [];

        $releasedAttributes = $this->getReleasedAttributes($entityId, $entityCategories);
        if ($releasedAttributes === null) {
            return [$this->entityIdAttribute => null];
        }

        $missingRequiredAttributes = array_diff($attributes[$this->attributesAttribute] ?? [], $releasedAttributes);
        if (!empty($missingRequiredAttributes)) {
            throw new \Exception('Missing required attributes ' . implode(',', $missingRequiredAttributes));
        }

        $result = [$this->attributesAttribute => $releasedAttributes];
        if ($this->tagsAttribute !== null) {
            $result[$this->tagsAttribute] = $this->tags;
        }
        return $result;
    }

    public function getReleasedAttributes($entityId, $entityCategories = [])
    {
        if (isset($this->releasedAttributes[$entityId])) {
            $attributes = $this->normalizeReleasedAttributes($this->releasedAttributes[$entityId], $entityCategories);
            $defaultAttributes = $this->getDefaultAttributes($entityCategories);
            if (
                $this->skipDefault
                && count($attributes) === count($defaultAttributes)
                && empty(array_diff($attributes, $defaultAttributes))
            ) {
                return null;
            }
                return $attributes;
        }
            return $this->skipDefault ? null : $this->releasedAttributesForAll;
    }

    public function getDefaultAttributes($entityCategories = [])
    {
        $attributes = $this->releasedAttributesForAll;
        foreach ($entityCategories as $entityCategory) {
            if (isset($this->entityCategories[$entityCategory])) {
                $attributes = array_merge($attributes, $this->entityCategories[$entityCategory]);
            } else {
                trigger_error('Skipping entity category ' . $entityCategory, E_USER_NOTICE);
            }
        }
        return $attributes;
    }

    private function parseAttributeFilter($data, $data_is_url)
    {
        $attributeFilterPolicyGroup = new SimpleXMLElement($data, 0, $data_is_url);
        foreach ($attributeFilterPolicyGroup->AttributeFilterPolicy as $policy) {
            $sps = [];
            $notSps = [];
            if (count($policy->PolicyRequirementRule) !== 1) {
                throw new \Exception('Not exactly one PolicyRequirementRule');
            }
            $requirement = $policy->PolicyRequirementRule;
            switch ($requirement->attributes('xsi', true)->type) {
                case 'basic:ANY':
                    $this->requirementAny($policy);
                    continue 2;
                case 'saml:AttributeRequesterEntityAttributeExactMatch':
                    trigger_error('Skipping ' . $requirement->attributes('xsi', true)->type, E_USER_NOTICE);
                    break;
                case 'basic:NOT':
                    $notSps = array_merge($notSps, $this->requirementSps($requirement));
                    break;
                case 'basic:AttributeRequesterString':
                    $sps[] = (string) $requirement->attributes()->value;
                    break;
                case 'basic:OR':
                    $sps = array_merge($sps, $this->requirementSps($requirement));
                    break;
                default:
                    throw new \Exception('Unsupported type ' . $requirement->attributes('xsi', true)->type);
            }
            $sps = array_diff($sps, $this->ignoreEntityIds, $notSps);
            $tag = (string) $policy['id'];
            foreach ($sps as $sp) {
                if (count($sps) > 1) {
                    $this->tags[$sp][] = $tag;
                }
                foreach ($policy->AttributeRule as $rule) {
                    $this->addAttributeTo($this->releasedAttributes[$sp], $rule);
                }
            }
        }
        $this->releasedAttributesForAll = $this->normalizeReleasedAttributes($this->releasedAttributesForAll);
    }

    private function normalizeReleasedAttributes($attributes, $entityCategories = [])
    {
        $arr = array_unique(array_merge($attributes, $this->getDefaultAttributes($entityCategories)));
        $arr = array_filter($arr, function ($attr) use ($attributes) {
            return substr($attr, 0, strlen(self::DENIED_ATTRIBUTE_PREFIX)) !== self::DENIED_ATTRIBUTE_PREFIX
                && !in_array(self::DENIED_ATTRIBUTE_PREFIX . $attr, $attributes, true);
        });
        sort($arr);
        return $arr;
    }

    private function requirementAny($policy)
    {
        foreach ($policy->AttributeRule as $rule) {
            addAttributeTo($this->releasedAttributesForAll, $rule);
        }
    }

    private function requirementSps($requirement)
    {
        $sps = [];
        foreach ($requirement->children('basic', true) as $rule) {
            if ($rule->getName() !== 'Rule') {
                continue;
            }
            switch ($rule->attributes('xsi', true)->type) {
                case 'basic:AttributeRequesterString':
                    $sps[] = (string) $rule->attributes()->value;
                    break;
                case 'basic:PrincipalNameString':
                    trigger_error('Skipping ' . $rule->attributes('xsi', true)->type, E_USER_NOTICE);
                    break;
                default:
                    throw new \Exception('Unsupported type ' . $rule->attributes('xsi', true)->type);
            }
        }
        return $sps;
    }

    private function addAttributeTo(&$array, $rule)
    {
        $attrName = (string) $rule['attributeID'];
        if (in_array($attrName, $this->ignoreAttributes, true)) {
            return;
        }
        foreach ($rule->PermitValueRule as $valueRule) {
            $type = $valueRule->attributes('xsi', true)->type;
            switch ($type) {
                case 'basic:ANY':
                    $array[] = $attrName;
                    break;
                case 'basic:AttributeValueRegex':
                    trigger_error('Skipping ' . $type, E_USER_NOTICE);
                    break;
                default:
                    throw new \Exception('Unsupported type ' . $type);
            }
        }
        foreach ($rule->DenyValueRule as $valueRule) {
            $type = $valueRule->attributes('xsi', true)->type;
            switch ($type) {
                case 'basic:ANY':
                    $array[] = self::DENIED_ATTRIBUTE_PREFIX . $attrName;
                    break;
                default:
                    throw new \Exception('Unsupported type ' . $type);
            }
        }
    }
}
