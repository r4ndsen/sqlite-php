<?php

declare(strict_types=1);

use r4ndsen\Rector\LowercaseVariableRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
        NullToStrictStringFuncCallArgRector::class,
        RemoveUnusedVariableAssignRector::class,
        LowercaseVariableRector::class,
    ])
    ->withSkip([
        // the fixtures are hydrated through reflection and must stay writable
        ReadOnlyPropertyRector::class => [__DIR__ . '/tests/SQLite/Traits/QueryTraitTest.php'],
    ]);
