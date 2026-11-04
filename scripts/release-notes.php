<?php

declare(strict_types=1);

/**
 * Prints the release notes for a version: the body of the changelog's `## [<version>]` section.
 * A repository that has not cut its first release yet has only `## [Unreleased]`, so that section
 * is the fallback. A version section that exists but is empty is an error, because a release with
 * no notes is a release nobody can read.
 *
 *   php scripts/release-notes.php 1.0.0-rc.1 > notes.md
 */

require __DIR__ . '/lib/cli.php';

/**
 * The body of the changelog section whose heading starts with the prefix, or null when it is missing.
 *
 * @param list<string> $lines
 */
function sectionBody(array $lines, string $prefix): ?string
{
    $body = [];
    $inside = false;
    foreach ($lines as $line) {
        if (str_starts_with($line, '## ')) {
            if ($inside) {
                break;
            }
            $inside = str_starts_with($line, $prefix);
            continue;
        }
        if ($inside) {
            $body[] = $line;
        }
    }

    return $inside ? trim(implode("\n", $body)) : null;
}

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
$text = sectionBody($lines, "## [{$version}]");
$section = $version;
if ($text === null) {
    $text = sectionBody($lines, '## [Unreleased]');
    $section = 'Unreleased';
}
if ($text === null) {
    fwrite(STDERR, "CHANGELOG.md has no section for {$version} and no [Unreleased] section\n");
    exit(1);
}
if ($text === '') {
    fwrite(STDERR, "the changelog's {$section} section is empty\n");
    exit(1);
}

fwrite(STDOUT, "# nepdate-php {$version}\n\n{$text}\n");
