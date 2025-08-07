<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\Transform\ValueObject\AttributeKeyToClassConstFetch;
use Rector\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector;

return RectorConfig::configure()
    ->withImportNames(importShortClasses: false)
    ->withCache(__DIR__ . '/build/rector')
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/bin',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php84: true)
    ->withAttributesSets(all: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: false,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: false,
        carbon: false,
        rectorPreset: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
    )->withSkip([
        ClosureToArrowFunctionRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        PreferPHPUnitThisCallRector::class,
        InlineIfToExplicitIfRector::class,
        LocallyCalledStaticMethodToNonStaticRector::class,
        ExplicitBoolCompareRector::class,
        NewlineAfterStatementRector::class,
        NewlineBeforeNewAssignSetRector::class,
        CatchExceptionNameMatchingTypeRector::class,

        // Temporarily disabled due to buggy upstream implementation
        TypedPropertyFromCreateMockAssignRector::class,

        AttributeKeyToClassConstFetch::class => [
            __DIR__ . '/src/Example/Entity/*',
        ],

        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/src/Example/Entity/*',
        ],
    ]);
