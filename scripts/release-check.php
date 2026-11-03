<?php

declare(strict_types=1);

/**
 * The release checks that are not covered by `composer ci`: the version in both manifests, the
 * constraint between them, and the changelog's Unreleased section (CONTRIBUTING.md).
 *
 *   php scripts/release-check.php 0.2.0-rc.1
 */

$root = dirname(__DIR__);
require __DIR__ . '/lib/cli.php';

$version = cliArgs()[1] ?? '';

if ($version === '') {
    fwrite(STDERR, "usage: php scripts/release-check.php <version>\n");
    exit(1);
}

$errors = [];

$manifestRaw = file_get_contents($root . '/composer.json');
$patroRaw = file_get_contents($root . '/packages/nepdate-patro/composer.json');
if ($manifestRaw === false || $patroRaw === false) {
    fwrite(STDERR, "cannot read a composer.json\n");
    exit(1);
}

/** @var array<string, mixed> $manifest */
$manifest = json_decode($manifestRaw, true, 512, JSON_THROW_ON_ERROR);
/** @var array<string, mixed> $patro */
$patro = json_decode($patroRaw, true, 512, JSON_THROW_ON_ERROR);

if (($manifest['version'] ?? null) !== $version) {
    $errors[] = 'composer.json version is ' . var_export($manifest['version'] ?? null, true) . ", expected {$version}";
}
if (($patro['version'] ?? null) !== $version) {
    $errors[] = 'packages/nepdate-patro/composer.json version is '
        . var_export($patro['version'] ?? null, true) . ", expected {$version}";
}

$require = $patro['require'] ?? null;
$constraint = is_array($require) ? ($require['sanamhub/nepdate'] ?? null) : null;
if ($constraint !== '^' . $version) {
    $errors[] = 'packages/nepdate-patro requires '
        . (is_string($constraint) ? $constraint : 'nothing') . ", expected ^{$version}";
}

$changelog = file_get_contents($root . '/CHANGELOG.md');
if ($changelog === false) {
    $errors[] = 'cannot read CHANGELOG.md';
} elseif (!str_contains($changelog, '## [Unreleased]')) {
    $errors[] = 'CHANGELOG.md has no ## [Unreleased] section';
}

if ($errors !== []) {
    fwrite(STDERR, "the release checks failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, "  - {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "the manifests and the changelog are ready for {$version}\n");
