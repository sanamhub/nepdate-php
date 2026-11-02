<?php

declare(strict_types=1);

namespace NepDate;

/**
 * A Nepali fiscal year, Shrawan 1 through the end of Ashad the following BS year
 * (ALGORITHM.md §9).
 */
final class FiscalYear
{
    public function __construct(
        public readonly int $startYear,
    ) {
    }

    public static function containing(NepaliDate $date): self
    {
        $startYear = $date->month() >= 4 ? $date->year() : $date->year() - 1;

        return new self($startYear);
    }

    public function start(): NepaliDate
    {
        return NepaliDate::fromBs($this->startYear, 4, 1);
    }

    public function end(): NepaliDate
    {
        $year = $this->startYear + 1;

        return NepaliDate::fromBs($year, 3, $this->endMonthLength());
    }

    public function range(): NepaliDateRange
    {
        return NepaliDateRange::of($this->start(), $this->end());
    }

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
        $endDate = NepaliDate::fromBs($endYear, $endMonth, \NepDate\Internal\Calendar::monthLength($endYear, $endMonth));

        return NepaliDateRange::of($startDate, $endDate);
    }

    public function label(Lang $lang = Lang::En): string
    {
        $next = ($this->startYear + 1) % 100;

        return $lang === Lang::Ne
            ? sprintf('आ.व. %d/%02d', $this->startYear, $next)
            : sprintf('FY %d/%02d', $this->startYear, $next);
    }

    private function endMonthLength(): int
    {
        return \NepDate\Internal\Calendar::monthLength($this->startYear + 1, 3);
    }
}
