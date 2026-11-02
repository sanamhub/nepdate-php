<?php

declare(strict_types=1);

namespace NepDate\Tests\Support;

/**
 * Loads the golden vector files under shared/spec/vectors/, shared by every language port
 * (PORT-PLAN.md). Handles the `#`-comment preamble, header row, and the `ERR`/`ERR:Kind`/
 * `none`/`;`-list/`..`-range notations used across the files.
 */
final class Vectors
{
    private const ROOT = __DIR__ . '/../../shared/spec/vectors';

    /**
     * @return list<array<string, string>> one associative row per data line, keyed by header
     */
    public static function load(string $relativePath, string $delimiter = "\t"): array
    {
        $path = self::ROOT . '/' . $relativePath;
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new \RuntimeException("cannot read vector file: {$path}");
        }

        $header = null;
        $rows = [];
        foreach ($lines as $line) {
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $cols = explode($delimiter, $line);
            if ($header === null) {
                $header = $cols;
                continue;
            }
            $row = [];
            foreach ($header as $i => $name) {
                $row[$name] = $cols[$i] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Like {@see load()}, but wraps each row as a single-element data-provider argument list,
     * since PHPUnit otherwise spreads an associative row's values as positional arguments.
     *
     * @return list<array{array<string, string>}>
     */
    public static function provider(string $relativePath, string $delimiter = "\t"): array
    {
        return array_map(static fn (array $row): array => [$row], self::load($relativePath, $delimiter));
    }

    /** Splits a `;`-separated list column into its items; returns [] for the `none` sentinel. */
    public static function list(string $value): array
    {
        if ($value === 'none' || $value === '') {
            return [];
        }

        return explode(';', $value);
    }

    /** Splits a `start..end` range-notation string into [start, end]. */
    public static function range(string $value): array
    {
        return explode('..', $value, 2);
    }

    public static function isError(string $value): bool
    {
        return $value === 'ERR' || str_starts_with($value, 'ERR:');
    }

    /** Extracts the `Kind` from an `ERR:Kind` cell, or null when the cell is plain `ERR`. */
    public static function errorKind(string $value): ?string
    {
        if (!str_starts_with($value, 'ERR:')) {
            return null;
        }

        return substr($value, 4);
    }
}
