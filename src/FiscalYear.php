<?php

declare(strict_types=1);

namespace NepDate;

use NepDate\Internal\Calendar;
use NepDate\Internal\Names;
use Stringable;

/**
 * A Nepali fiscal year, Shrawan 1 through the end of Ashad the following BS year
 * (ALGORITHM.md §9, API.md NY01-NY10).
 *
 * The value carries the BS year in which it starts, so a date in months 1 to 3 belongs to the
 * fiscal year that started in the previous BS year.
 */
final class FiscalYear implements Stringable
{
    public function __construct(
        public readonly int $startYear,
    ) {
    }

    /** The fiscal year that contains `$date`. */
    public static function containing(NepaliDate $date): self
    {
        $startYear = $date->month() >= 4 ? $date->year() : $date->year() - 1;

        return new self($startYear);
    }

    /** 1 Shrawan of the fiscal year. */
    public function start(): NepaliDate
    {
        return NepaliDate::fromBs($this->startYear, 4, 1);
    }

    /** The last day of Ashad of the following BS year. */
    public function end(): NepaliDate
    {
        $year = $this->startYear + 1;

        return NepaliDate::fromBs($year, 3, Calendar::monthLength($year, 3));
    }

    public function range(): NepaliDateRange
    {
        return NepaliDateRange::of($this->start(), $this->end());
    }

    /** The quarter `$quarter` of this fiscal year, clipped to the fiscal year's own bounds. */
    public function quarterRange(Quarter $quarter): NepaliDateRange
    {
        [$startMonth, $startYearOffset, $endMonth, $endYearOffset] = match ($quarter) {
            Quarter::Q1 => [4, 0, 6, 0],
            Quarter::Q2 => [7, 0, 9, 0],
            Quarter::Q3 => [10, 0, 12, 0],
            Quarter::Q4 => [1, 1, 3, 1],
        };

        $startYear = $this->startYear + $startYearOffset;
        $endYear = $this->startYear + $endYearOffset;
        $startDate = NepaliDate::fromBs($startYear, $startMonth, 1);
        $endDate = NepaliDate::fromBs($endYear, $endMonth, Calendar::monthLength($endYear, $endMonth));

        return NepaliDateRange::of($startDate, $endDate);
    }

    /** The label, `2081/82`, with the digits of `$lang`. */
    public function label(Lang $lang = Lang::En): string
    {
        $next = Names::dayDigits(($this->startYear + 1) % 100, true, $lang);

        return Names::yearDigits($this->startYear, $lang) . '/' . $next;
    }

    public function equals(self $other): bool
    {
        return $this->startYear === $other->startYear;
    }

    public function __toString(): string
    {
        return $this->label();
    }
}
