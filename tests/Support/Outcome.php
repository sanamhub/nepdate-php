<?php

declare(strict_types=1);

namespace NepDate\Tests\Support;

use NepDate\Lang;
use NepDate\NepaliDate;
use NepDate\NepaliDateRange;
use NepDate\NepDateException;
use PHPUnit\Framework\Assert;

/**
 * Turns a call into the string a golden vector records: an ISO date, a `start..end` range, a
 * `;`-separated list, `none` for an absent result, or `ERR`/`ERR:Kind` for a failure.
 *
 * The vector cells are the C# recorded text, so a plain `ERR` cell matches any error and an
 * `ERR:Kind` cell pins the {@see \NepDate\ErrorKind} name (PARSING.md §4).
 */
final class Outcome
{
    /**
     * The ISO date for `$call`, or the error cell.
     *
     * @param callable(): mixed $call
     */
    public static function date(callable $call): string
    {
        try {
            $value = $call();
        } catch (NepDateException $error) {
            return self::error($error);
        }

        return $value instanceof NepaliDate ? $value->isoformat() : self::unexpected($value);
    }

    /**
     * `start..end` for `$call`, `none` when it returns null, or the error cell.
     *
     * @param callable(): mixed $call
     */
    public static function range(callable $call): string
    {
        try {
            $value = $call();
        } catch (NepDateException $error) {
            return self::error($error);
        }

        if ($value === null) {
            return 'none';
        }

        return $value instanceof NepaliDateRange ? self::rangeText($value) : self::unexpected($value);
    }

    /**
     * A `;`-separated list of ISO dates, `none` when empty, or the error cell.
     *
     * @param callable(): mixed $call
     */
    public static function dateList(callable $call): string
    {
        try {
            $value = $call();
        } catch (NepDateException $error) {
            return self::error($error);
        }

        if (!is_iterable($value)) {
            return self::unexpected($value);
        }

        $parts = [];
        foreach ($value as $date) {
            if (!$date instanceof NepaliDate) {
                return self::unexpected($date);
            }
            $parts[] = $date->isoformat();
        }

        return $parts === [] ? 'none' : implode(';', $parts);
    }

    /**
     * A `;`-separated list of `start..end` ranges, `none` when empty, or the error cell.
     *
     * @param callable(): mixed $call
     */
    public static function rangeList(callable $call): string
    {
        try {
            $value = $call();
        } catch (NepDateException $error) {
            return self::error($error);
        }

        if (!is_iterable($value)) {
            return self::unexpected($value);
        }

        $parts = [];
        foreach ($value as $range) {
            if (!$range instanceof NepaliDateRange) {
                return self::unexpected($range);
            }
            $parts[] = self::rangeText($range);
        }

        return $parts === [] ? 'none' : implode(';', $parts);
    }

    /**
     * A scalar as the vectors write it: `true`/`false`, an integer, a string, or the error cell.
     *
     * @param callable(): mixed $call
     */
    public static function scalar(callable $call): string
    {
        try {
            $value = $call();
        } catch (NepDateException $error) {
            return self::error($error);
        }

        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) => (string) $value,
            is_string($value) => $value,
            default => self::unexpected($value),
        };
    }

    /** `start..end` in the vectors' notation. */
    public static function rangeText(NepaliDateRange $range): string
    {
        return $range->start()->isoformat() . '..' . $range->end()->isoformat();
    }

    /** The vector's date notation `YYYY-MM-DD` as a date. */
    public static function bs(string $text): NepaliDate
    {
        return NepaliDate::parse($text);
    }

    /** The vector's range notation `YYYY-MM-DD..YYYY-MM-DD` as a range. */
    public static function parseRange(string $text): NepaliDateRange
    {
        $parts = Vectors::range($text);
        $start = $parts[0] ?? '';
        $end = $parts[1] ?? '';

        return NepaliDateRange::of(self::bs($start), self::bs($end));
    }

    public static function lang(string $code): Lang
    {
        return $code === 'ne' ? Lang::Ne : Lang::En;
    }

    /**
     * Asserts that the produced cell satisfies the vector's cell: a plain `ERR` accepts any
     * error, `ERR:Kind` accepts only that kind, anything else is compared literally.
     */
    public static function assertMatches(string $expected, string $actual, string $reason = ''): void
    {
        if ($expected === 'ERR') {
            Assert::assertTrue(
                $actual === 'ERR' || preg_match('/^ERR:[A-Z]/', $actual) === 1,
                trim("expected any error, got {$actual}. {$reason}"),
            );

            return;
        }

        Assert::assertSame($expected, $actual, $reason);
    }

    private static function error(NepDateException $error): string
    {
        return 'ERR:' . $error->kind()->name;
    }

    private static function unexpected(mixed $value): string
    {
        $type = get_debug_type($value);

        return "unexpected {$type}";
    }
}
