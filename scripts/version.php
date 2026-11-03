<?php

declare(strict_types=1);

/**
 * Reads the version both manifests share and, with `--check-tag`, checks it against a git tag.
 *
 *   php scripts/version.php                    print the version
 *   php scripts/version.php --check-tag v0.2.0-rc.1
 *
 * The two packages release on one tag (ADR-0004 decision 2), so this is the single place the
 * version is read from; `ci.yml` publishes it as an output for the shared-check job.
 */

$root = dirname(__DIR__);
$manifest = $root . '/composer.json';
$patroManifest = $root . '/packages/nepdate-patro/composer.json';

/**
 * @return array<string, mixed>
 */
function readManifest(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        fwrite(STDERR, "cannot read {$path}\n");
        exit(1);
    }

    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        fwrite(STDERR, "{$path} does not hold an object\n");
        exit(1);
    }

    /** @var array<string, mixed> $decoded */
    return $decoded;
}

function fail(string $message): never
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

$core = readManifest($manifest);
$patro = readManifest($patroManifest);

$version = $core['version'] ?? null;
if (!is_string($version)) {
    fail('composer.json has no version');
}

if ($version === '') {
    fail('composer.json has an empty version');
}

$patroVersion = $patro['version'] ?? null;
if (is_string($patroVersion) && $patroVersion !== '' && $patroVersion !== $version) {
    fail("packages/nepdate-patro declares {$patroVersion} but the core declares {$version}");
}

$require = $patro['require'] ?? null;
$constraint = is_array($require) ? ($require['sanamhub/nepdate'] ?? null) : null;
if (!is_string($constraint)) {
    fail('packages/nepdate-patro does not require sanamhub/nepdate');
}
if ($constraint !== '^' . $version) {
    fail("packages/nepdate-patro requires {$constraint}, expected ^" . $version);
}

require __DIR__ . '/lib/cli.php';

$args = array_slice(cliArgs(), 1);
if ($args === []) {
    fwrite(STDOUT, $version . "\n");
    exit(0);
}

if ($args[0] !== '--check-tag') {
    fail('usage: php scripts/version.php [--check-tag <tag>]');
}

$tag = $args[1] ?? '';
if ($tag !== 'v' . $version) {
    fail("the tag {$tag} and the manifest version {$version} disagree");
}

fwrite(STDOUT, $version . "\n");
