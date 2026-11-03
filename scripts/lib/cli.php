<?php

declare(strict_types=1);

/**
 * The command-line arguments of a script, without the script name.
 *
 * `$_SERVER['argv']` is typed as mixed, so every script reads it through this one function instead
 * of carrying its own narrowing. The generator and release scripts are the only command-line
 * entry points in the repository.
 */

/**
 * @return list<string>
 */
function cliArgs(): array
{
    $argv = $_SERVER['argv'] ?? [];
    if (!is_array($argv)) {
        return [];
    }

    $args = [];
    foreach ($argv as $arg) {
        if (is_string($arg)) {
            $args[] = $arg;
        }
    }

    return $args;
}
