<?php

declare(strict_types=1);

/**
 * Prints the release notes for a version: the body of the changelog's `## [Unreleased]` section,
 * which is the only section the changelog has before the first release (CONTRIBUTING.md).
 *
 *   php scripts/release-notes.php 0.2.0-rc.1 > notes.md
 */

require __DIR__ . '/lib/cli.php';

$version = cliArgs()[1] ?? '';
if ($version === '') {
    fwrite(STDERR, "usage: php scripts/release-notes.php <version>\n");
    exit(1);
}

$changelog = file_get_contents(dirname(__DIR__) . '/CHANGELOG.md');
if ($changelog === false) {
    fwrite(STDERR, "cannot read CHANGELOG.md\n");
    exit(1);
}

$lines = explode("\n", $changelog);
$body = [];
$inside = false;
foreach ($lines as $line) {
    if (str_starts_with($line, '## ')) {
        if ($inside) {
            break;
        }
        $inside = str_contains($line, '[Unreleased]');
        continue;
    }
    if ($inside) {
        $body[] = $line;
    }
}

$text = trim(implode("\n", $body));
if ($text === '') {
    fwrite(STDERR, "the changelog's Unreleased section is empty\n");
    exit(1);
}

fwrite(STDOUT, "# nepdate-php {$version}\n\n{$text}\n");
