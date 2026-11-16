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
    /** The length of BS month `$month` (1-12) of BS year `$year`, 29 through 32. */
    public static function monthLength(int $year, int $month): int
    {
        if ($year < CalendarTable::MIN_YEAR || $year > CalendarTable::MAX_YEAR) {
            throw new NepDateException(
                ErrorKind::OutOfRange,
                "year {$year} is outside " . CalendarTable::MIN_YEAR . '..' . CalendarTable::MAX_YEAR,
            );
        }
        $index = ($year - CalendarTable::MIN_YEAR) * 12 + $month - 1;

        return CalendarTable::MONTH_START[$index + 1] - CalendarTable::MONTH_START[$index];
    }

    /**
     * The serial day number (0-based from 1901-01-01 BS) of a BS date, after checking the year,
     * the month and the day in that order.
     */
    public static function serialOf(int $year, int $month, int $day): int
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
        $index = ($year - CalendarTable::MIN_YEAR) * 12 + $month - 1;
        $start = CalendarTable::MONTH_START[$index];
        $length = CalendarTable::MONTH_START[$index + 1] - $start;
        if ($day < 1 || $day > $length) {
            throw new NepDateException(ErrorKind::InvalidDay, "day {$day} is outside 1..{$length}");
        }

        return $start + $day - 1;
    }

    /**
     * The month index `(year - MIN_YEAR) * 12 + month - 1` of the month that holds a valid serial.
     *
     * A BS month is 30.44 days long on average, so the index is first estimated by proportion and
     * then corrected by a step or two.
     */
    public static function monthIndexOf(int $serial): int
    {
        $starts = CalendarTable::MONTH_START;
        $index = (int) ($serial * CalendarTable::MONTHS / CalendarTable::TOTAL_DAYS);
        while ($starts[$index] > $serial) {
            $index--;
        }
        while ($starts[$index + 1] <= $serial) {
            $index++;
        }

        return $index;
    }

    /**
     * @return array{0: int, 1: int, 2: int} year, month, day for a valid serial
     */
    public static function fromSerial(int $serial): array
    {
        $index = self::monthIndexOf($serial);

        return [
            CalendarTable::MIN_YEAR + (int) ($index / 12),
            $index % 12 + 1,
            $serial - CalendarTable::MONTH_START[$index] + 1,
        ];
    }

    public static function validate(int $year, int $month, int $day): void
    {
        self::serialOf($year, $month, $day);
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
}
