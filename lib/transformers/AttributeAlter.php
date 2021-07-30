<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\perun\transformers;

use SimpleSAML\Module\perun\SingularAttributeTransformer;

/**
 * Performs replacements. Based on SSP built in core:AttributeAlter authentication processing filter.
 *
 * @see https://simplesamlphp.org/docs/stable/core:authproc_attributealter
 */
class AttributeAlter extends SingularAttributeTransformer
{
    public const ATTRIBUTES_KEY = 'Attributes';

    public const SUBJECT = 'subject';

    private $config;

    private $configArray;

    /**
     * @override
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        $this->config = $config;
        $this->configArray = $config->toArray();
    }

    /**
     * @override
     */
    public function singleTransform($values)
    {
        $config = array_merge([], $this->configArray);
        $config['subject'] = self::SUBJECT;
        $filter = new \SimpleSAML\Module\core\Auth\Process\AttributeAlter($config, null);
        $request = [
            self::ATTRIBUTES_KEY => [
                self::SUBJECT => $values,

            ],
        ];
        $filter->process($request);
        return $request[self::ATTRIBUTES_KEY][self::SUBJECT] ?? null;
    }

    /**
     * @override
     */
    public function singleDescription(string $description)
    {
        if (in_array('%remove', $this->configArray, true)) {
            return sprintf('remove %s from (%s)', $this->config->getString('pattern'), $description);
        }
        if (in_array('%replace', $this->configArray, true)) {
            if (in_array('replacement', $this->configArray, true)) {
                return sprintf(
                    'if (%s) matches %s then %s else (%s)',
                    $description,
                    $this->config->getString('pattern'),
                    $this->config->getString('replacement'),
                    $description
                );
            }
            return sprintf(
                'if (%s) matches %s then %s else (%s)',
                $description,
                $this->config->getString('pattern'),
                '$0',
                $description
            );
        }
        if ($this->config->getString('pattern') === '/^/') {
            return sprintf('prepend %s to (%s)', $this->config->getString('replacement'), $description);
        }
        return sprintf(
            'replace %s with %s in (%s)',
            $this->config->getString('pattern'),
            $this->config->getString('replacement') ?: '""',
            $description
        );
    }
}
