<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/ecs.php',
        __DIR__ . '/config-templates',
        __DIR__ . '/hooks',
        __DIR__ . '/lib',
        __DIR__ . '/templates',
        __DIR__ . '/themes',
        __DIR__ . '/www',
    ]);

    $ecsConfig->sets([
        SetList::CLEAN_CODE,
        SetList::SYMPLIFY,
        SetList::ARRAY,
        SetList::COMMON,
        SetList::COMMENTS,
        SetList::CONTROL_STRUCTURES,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::PHPUNIT,
        SetList::SPACES,
        SetList::STRICT,
        SetList::PSR_12,
    ]);

    $ecsConfig->skip([NotOperatorWithSuccessorSpaceFixer::class, FunctionTypehintSpaceFixer::class]);
};
