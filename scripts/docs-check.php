<?php

declare(strict_types=1);

/**
 * Runs every PHP code block in README.md and docs/API.md (ADR-0006).
 *
 *   php scripts/docs-check.php
 *
 * A line of the form `EXPRESSION; // => LITERAL` is checked: the block fails when the expression
 * does not equal the literal. Any other line is executed for its side effect, so a block that
 * throws fails too. Nothing is written outside the temporary directory and no network is used.
 */

$root = dirname(__DIR__);
$documents = [$root . '/README.md', $root . '/docs/API.md'];

require $root . '/vendor/autoload.php';

$failures = [];
$blocks = 0;
$checks = 0;

$imports = <<<'PHP'
    use NepDate\DateOrder;
    use NepDate\ErrorKind;
    use NepDate\FiscalYear;
    use NepDate\Lang;
    use NepDate\Month;
    use NepDate\MonthOverflow;
    use NepDate\NepDateException;
    use NepDate\NepaliDate;
    use NepDate\NepaliDateRange;
    use NepDate\Quarter;
    use NepDate\Separator;
    use NepDate\Weekday;

    use function NepDate\Patro\coverage;
    use function NepDate\Patro\info;
    use function NepDate\Patro\publicHolidays;

    PHP;

$helpers = <<<'PHP'
    $__checked = 0;
    $__failed = [];
    $__ok = static function (bool $passed, string $what, mixed $actual, string $expected) use (&$__checked, &$__failed): void {
        $__checked++;
        if (!$passed) {
            $__failed[] = $what . ' produced ' . var_export($actual, true) . ', expected ' . $expected;
        }
    };

    PHP;

$header = "<?php\n\ndeclare(strict_types=1);\n\n"
    . $imports
    . 'require ' . var_export($root . '/vendor/autoload.php', true) . ";\n\n"
    . $helpers;

foreach ($documents as $document) {
    $text = file_get_contents($document);
    if ($text === false) {
        $failures[] = "cannot read {$document}";
        continue;
    }

    if (preg_match_all('/```php\n(.*?)```/s', $text, $matches) === 0) {
        continue;
    }

    foreach ($matches[1] as $index => $code) {
        $blocks++;
        $label = basename($document) . ' block ' . ($index + 1);
        $script = $header;

        foreach (explode("\n", rtrim($code)) as $line) {
            if (preg_match('/^(\s*)(.+);\s*\/\/\s*=>\s*throws\s+(?:NepDateException|\w+Exception)\((\w+)\)\s*$/', rtrim($line), $found) === 1) {
                $expression = $found[2];
                $kind = $found[3];
                $script .= "\$__kind = null;\n";
                $script .= "try {\n    {$expression};\n} catch (NepDateException \$__error) {\n    \$__kind = \$__error->kind()->name;\n}\n";
                $script .= '$__ok($__kind === ' . var_export($kind, true) . ', '
                    . var_export($expression, true) . ', $__kind, ' . var_export($kind, true) . ");\n";
                continue;
            }
            if (preg_match('/^(\s*)(.+);\s*\/\/\s*=>\s*(.+)$/', rtrim($line), $found) === 1) {
                $expression = $found[2];
                $expected = $found[3];
                $script .= "\$__value = {$expression};\n";
                $script .= "\$__ok(\$__value === {$expected}, " . var_export($expression, true)
                    . ', $__value, ' . var_export($expected, true) . ");\n";
                continue;
            }
            $script .= $line . "\n";
        }

        $script .= <<<'PHP'

            if ($__failed !== []) {
                fwrite(STDERR, implode("\n", $__failed) . "\n");
                exit(1);
            }

            fwrite(STDOUT, $__checked . "\n");
            exit(0);

            PHP;

        $path = sys_get_temp_dir() . '/nepdate-docs-' . getmypid() . '-' . $blocks . '.php';
        file_put_contents($path, $script);

        $output = [];
        $status = 0;
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($path) . ' 2>&1', $output, $status);
        unlink($path);

        if ($status !== 0) {
            $failures[] = "{$label} failed:\n    " . implode("\n    ", $output);
            continue;
        }

        $checks += (int) ($output[0] ?? 0);
    }
}

if ($failures !== []) {
    fwrite(STDERR, "the documentation examples failed:\n" . implode("\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "the documentation examples pass ({$blocks} blocks, {$checks} checked values)\n");
