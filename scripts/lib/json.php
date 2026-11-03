<?php

declare(strict_types=1);

/**
 * Typed reading of the JSON files under shared/data/ for the table generators.
 *
 * The generators are the only code that ever parses JSON (CONTRIBUTING.md); every table they
 * emit is a PHP constant. Strict helpers keep a malformed data file a loud error instead of a
 * silently wrong table, and they give static analysis a real type to work with.
 */

/**
 * @return array<string, mixed>
 */
function readJsonObject(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("cannot read {$path}");
    }

    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded) || array_is_list($decoded)) {
        throw new RuntimeException("{$path} does not hold a JSON object");
    }

    /** @var array<string, mixed> $decoded */
    return $decoded;
}

function jsonString(mixed $value, string $what): string
{
    if (!is_string($value)) {
        throw new RuntimeException("{$what} must be a string, got " . get_debug_type($value));
    }

    return $value;
}

function jsonInt(mixed $value, string $what): int
{
    if (!is_int($value)) {
        throw new RuntimeException("{$what} must be an integer, got " . get_debug_type($value));
    }

    return $value;
}

/**
 * @return array<string, mixed>
 */
function jsonObject(mixed $value, string $what): array
{
    if (!is_array($value) || array_is_list($value)) {
        throw new RuntimeException("{$what} must be a JSON object, got " . get_debug_type($value));
    }

    /** @var array<string, mixed> $value */
    return $value;
}

/**
 * @return list<mixed>
 */
function jsonList(mixed $value, string $what): array
{
    if (!is_array($value)) {
        throw new RuntimeException("{$what} must be a JSON array, got " . get_debug_type($value));
    }

    return array_values($value);
}

/**
 * @return list<int>
 */
function jsonIntList(mixed $value, string $what): array
{
    $out = [];
    foreach (jsonList($value, $what) as $item) {
        $out[] = jsonInt($item, $what);
    }

    return $out;
}
