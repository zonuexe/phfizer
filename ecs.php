<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\UnaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRules([
        NoUnusedImportsFixer::class,
    ])
    ->withConfiguredRule(PhpdocLineSpanFixer::class, [
        'const' => 'single',
        'method' => 'single',
        'property' => 'single',
    ])
    ->withPreparedSets(
        psr12: true,
        arrays: true,
        namespaces: true,
        spaces: true,
        docblocks: true,
        comments: true,
    )
    ->withSkip([
        UnaryOperatorSpacesFixer::class,
    ]);
