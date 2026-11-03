<?php

declare(strict_types=1);

/**
 * Code style for the two packages: PSR-12 and the PHP 8.3 migration rules, plus the few extra
 * fixers this repository relies on. Run `composer cs` to check and `composer cs:fix` to apply.
 */

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/scripts')
    ->in(__DIR__ . '/bench')
    ->in(__DIR__ . '/packages/nepdate-patro/src')
    ->in(__DIR__ . '/packages/nepdate-patro/tests')
    ->in(__DIR__ . '/packages/nepdate-patro/scripts')
    ->exclude('bench/competitors')
    ->exclude('competitors')
    ->notName('CalendarTable.php')
    ->notName('PatroTable.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PHP83Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha', 'imports_order' => ['class', 'function', 'const']],
        'single_quote' => true,
        'native_function_invocation' => false,
        'no_superfluous_phpdoc_tags' => false,
        'phpdoc_to_comment' => false,
        'single_line_throw' => false,
        'yoda_style' => false,
    ])
    ->setFinder($finder);
