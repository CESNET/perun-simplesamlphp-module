<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\AttributeTransformer;

/**
 * Parse Shibboleth attribute filter and get released attributes.
 */
class ShibbolethAttributeFilter extends AttributeTransformer
{
    public const DENIED_ATTRIBUTE_PREFIX = '-';

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

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->ignoreAttributes = $config->getArray('ignore.attributes', []);
        $this->ignoreEntityIds = $config->getArray('ignore.entityIDs', []);
        $this->entityCategories = $config->getArray('entityCategories', []);
        $this->entityIdAttribute = \SimpleSAML\Module\perun\MetadataToPerun::ENTITY_ID;
        $this->attributesAttribute = $config->getString('attributesAttribute', 'requiredAttributes');
        $this->entityCategoriesAttribute = $config->getString('entityCategoriesAttribute', 'entityCategory');
        $this->tagsAttribute = $config->getString('tagsAttribute', null);
        $this->skipDefault = $config->getBoolean('skipDefault', false);
        $this->throwOnMismatch = $config->getBoolean('throwOnMismatch', false);

        $data = $config->getString('file', null);
        if ($data !== null) {
            $data_is_url = true;
        } else {
            $data_is_url = false;
            $data = $config->getString('xml');
        }
        $this->parseAttributeFilter($data, $data_is_url);
    }

    /**
     * @override
     */
    public function transform(array $attributes)
    {
        if (empty($attributes[$this->entityIdAttribute])) {
            self::error('entityId is missing');
        }
        $entityId = $attributes[$this->entityIdAttribute];
        $entityCategories = $attributes[$this->entityCategoriesAttribute] ?? [];

        $releasedAttributes = $this->getReleasedAttributes($entityId, $entityCategories);
        if ($releasedAttributes === null) {
            return [
                $this->entityIdAttribute => null,
            ];
        }

        $missingRequiredAttributes = array_diff($attributes[$this->attributesAttribute] ?? [], $releasedAttributes);
        if (! empty($missingRequiredAttributes)) {
            $message = 'Missing required attributes ' . implode(',', $missingRequiredAttributes);
            if ($this->throwOnMismatch) {
                self::error($message);
            } else {
                self::warning($message);
            }
        }

        $result = [
            $this->attributesAttribute => $releasedAttributes,
        ];
        if ($this->tagsAttribute !== null && ! empty($this->tags[$entityId])) {
            $result[$this->tagsAttribute] = $this->tags[$entityId];
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
                self::warning('Skipping entity category ' . $entityCategory);
            }
        }
        return $attributes;
    }

    public function getDescription(array $attributes)
    {
        $description = $attributes[$this->attributesAttribute];
        $d = [
            $this->attributesAttribute => sprintf(
                'currently released attributes from internal Shibboleth configuration if superset of (%s)',
                $description
            ),
        ];
        if ($this->tagsAttribute !== null) {
            $d[$this->tagsAttribute] = sprintf('internal tags from Shibboleth configuration');
        }
        return $d;
    }

    private static function error($message)
    {
        throw new \Exception($message);
    }

    private static function warning($message)
    {
        \SimpleSAML\Logger::info('ShibbolethAttributeFilter: ' . $message);
    }

    private function parseAttributeFilter($data, $data_is_url)
    {
        $attributeFilterPolicyGroup = new \SimpleXMLElement($data, 0, $data_is_url);
        foreach ($attributeFilterPolicyGroup->AttributeFilterPolicy as $policy) {
            $sps = [];
            $notSps = [];
            if (count($policy->PolicyRequirementRule) !== 1) {
                self::error('Not exactly one PolicyRequirementRule');
            }
            $requirement = $policy->PolicyRequirementRule;
            switch ($requirement->attributes('xsi', true)->type) {
                case 'basic:ANY':
                    $this->requirementAny($policy);
                    continue 2;
                case 'saml:AttributeRequesterEntityAttributeExactMatch':
                    self::warning('Skipping ' . $requirement->attributes('xsi', true)->type);
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
                    self::error('Unsupported type ' . $requirement->attributes('xsi', true)->type);
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
                && ! in_array(self::DENIED_ATTRIBUTE_PREFIX . $attr, $attributes, true);
        });
        sort($arr);
        return $arr;
    }

    private function requirementAny($policy)
    {
        foreach ($policy->AttributeRule as $rule) {
            $this->addAttributeTo($this->releasedAttributesForAll, $rule);
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
                    self::warning('Skipping ' . $rule->attributes('xsi', true)->type);
                    break;
                default:
                    self::error('Unsupported type ' . $rule->attributes('xsi', true)->type);
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
            $type = $valueRule->attributes('xsi', true)
                ->type;
            switch ($type) {
                case 'basic:ANY':
                    $array[] = $attrName;
                    break;
                case 'basic:AttributeValueRegex':
                    self::warning('Skipping ' . $type);
                    break;
                default:
                    self::error('Unsupported type ' . $type);
            }
        }
        foreach ($rule->DenyValueRule as $valueRule) {
            $type = $valueRule->attributes('xsi', true)
                ->type;
            switch ($type) {
                case 'basic:ANY':
                    $array[] = self::DENIED_ATTRIBUTE_PREFIX . $attrName;
                    break;
                default:
                    self::error('Unsupported type ' . $type);
            }
        }
    }
}
