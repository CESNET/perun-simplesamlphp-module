<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/ecs.php',
        __DIR__ . '/config-templates',
        __DIR__ . '/hooks',
        __DIR__ . '/lib',
        __DIR__ . '/templates',
        __DIR__ . '/themes',
        __DIR__ . '/www',
    ]);
    $parameters->set(Option::PARALLEL, true);
    $parameters->set(Option::SKIP, [NotOperatorWithSuccessorSpaceFixer::class, FunctionTypehintSpaceFixer::class]);

    $containerConfigurator->import(SetList::PHP_CS_FIXER);
    $containerConfigurator->import(SetList::CLEAN_CODE);
    $containerConfigurator->import(SetList::SYMPLIFY);
    $containerConfigurator->import(SetList::ARRAY);
    $containerConfigurator->import(SetList::COMMON);
    $containerConfigurator->import(SetList::COMMENTS);
    $containerConfigurator->import(SetList::CONTROL_STRUCTURES);
    $containerConfigurator->import(SetList::DOCBLOCK);
    $containerConfigurator->import(SetList::NAMESPACES);
    $containerConfigurator->import(SetList::PHPUNIT);
    $containerConfigurator->import(SetList::SPACES);
    $containerConfigurator->import(SetList::STRICT);
    $containerConfigurator->import(SetList::SYMFONY);
    $containerConfigurator->import(SetList::PSR_12);

    $services = $containerConfigurator->services();
    $services->set(ArraySyntaxFixer::class)
        ->call('configure', [[
            'syntax' => 'short',
        ]])
    ;
};
