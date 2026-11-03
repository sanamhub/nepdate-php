<?php

declare(strict_types=1);

namespace NepDate\Internal;

use NepDate\ErrorKind;
use NepDate\NepDateException;

/**
 * BS serial arithmetic and Gregorian civil-date conversion (ALGORITHM.md §2-6).
 *
 * @internal
 */
final class Calendar
{
    /** @var list<int> January to December of a common Gregorian year. */
    private const array GREGORIAN_MONTH_LENGTHS = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    /** The length of BS month `$month` (1-12) of BS year `$year`, 29 through 32. */
    public static function monthLength(int $year, int $month): int
    {
        $index = $year - CalendarTable::MIN_YEAR;
        if ($index < 0 || $index >= CalendarTable::YEARS) {
            throw new NepDateException(
                ErrorKind::OutOfRange,
                "year {$year} is outside " . CalendarTable::MIN_YEAR . '..' . CalendarTable::MAX_YEAR,
            );
        }

        $packed = CalendarTable::PACKED[$index];

        return 29 + (($packed >> (2 * ($month - 1))) & 0b11);
    }

    /** The serial day number (0-based from 1901-01-01 BS) of a validated BS date. */
    public static function toSerial(int $year, int $month, int $day): int
    {
        $serial = CalendarTable::YEAR_START[$year - CalendarTable::MIN_YEAR];
        for ($m = 1; $m < $month; $m++) {
            $serial += self::monthLength($year, $m);
        }

        return $serial + $day - 1;
    }

    /**
     * @return array{0: int, 1: int, 2: int} year, month, day for a valid serial
     */
    public static function fromSerial(int $serial): array
    {
        $years = CalendarTable::YEAR_START;
        $lo = 0;
        $hi = CalendarTable::YEARS - 1;
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi + 1, 2);
            if ($years[$mid] <= $serial) {
                $lo = $mid;
            } else {
                $hi = $mid - 1;
            }
        }
        $year = CalendarTable::MIN_YEAR + $lo;
        $remaining = $serial - $years[$lo];
        $month = 1;
        while (true) {
            $length = self::monthLength($year, $month);
            if ($remaining < $length) {
                break;
            }
            $remaining -= $length;
            $month++;
        }

        return [$year, $month, $remaining + 1];
    }

    public static function validate(int $year, int $month, int $day): void
    {
        if ($year < CalendarTable::MIN_YEAR || $year > CalendarTable::MAX_YEAR) {
            throw new NepDateException(
                ErrorKind::OutOfRange,
                "year {$year} is outside " . CalendarTable::MIN_YEAR . '..' . CalendarTable::MAX_YEAR,
            );
        }
        if ($month < 1 || $month > 12) {
            throw new NepDateException(ErrorKind::InvalidMonth, "month {$month} is outside 1..12");
        }
        $length = self::monthLength($year, $month);
        if ($day < 1 || $day > $length) {
            throw new NepDateException(ErrorKind::InvalidDay, "day {$day} is outside 1..{$length}");
        }
    }

    /** Proleptic Gregorian civil date to days since 1970-01-01 (Howard Hinnant's algorithm). */
    public static function daysFromCivil(int $y, int $m, int $d): int
    {
        $y -= $m <= 2 ? 1 : 0;
        $era = intdiv($y >= 0 ? $y : $y - 399, 400);
        $yoe = $y - $era * 400;
        $mp = ($m + 9) % 12;
        $doy = intdiv($mp * 153 + 2, 5) + $d - 1;
        $doe = $yoe * 365 + intdiv($yoe, 4) - intdiv($yoe, 100) + $doy;

        return $era * 146097 + $doe - 719468;
    }

    /**
     * @return array{0: int, 1: int, 2: int} proleptic Gregorian year, month, day
     */
    public static function civilFromDays(int $z): array
    {
        $z += 719468;
        $era = intdiv($z >= 0 ? $z : $z - 146096, 146097);
        $doe = $z - $era * 146097;
        $yoe = intdiv($doe - intdiv($doe, 1460) + intdiv($doe, 36524) - intdiv($doe, 146096), 365);
        $y = $yoe + $era * 400;
        $doy = $doe - (365 * $yoe + intdiv($yoe, 4) - intdiv($yoe, 100));
        $mp = intdiv(5 * $doy + 2, 153);
        $d = $doy - intdiv($mp * 153 + 2, 5) + 1;
        $m = $mp < 10 ? $mp + 3 : $mp - 9;
        $y += $m <= 2 ? 1 : 0;

        return [$y, $m, $d];
    }

    /** Whether `$y-$m-$d` is a real proleptic Gregorian date. */
    public static function isValidGregorian(int $y, int $m, int $d): bool
    {
        if ($m < 1 || $m > 12) {
            return false;
        }

        return $d >= 1 && $d <= self::gregorianMonthLength($y, $m);
    }

    public static function gregorianMonthLength(int $y, int $m): int
    {
        if ($m === 2 && self::isGregorianLeapYear($y)) {
            return 29;
        }

        return self::GREGORIAN_MONTH_LENGTHS[$m - 1];
    }

    public static function isGregorianLeapYear(int $y): bool
    {
        return ($y % 4 === 0 && $y % 100 !== 0) || $y % 400 === 0;
    }
}
