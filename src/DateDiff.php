<?php

declare(strict_types=1);

namespace NepDate;

/**
 * The signed years, months and days between two dates (ALGORITHM.md §8a).
 */
final class DateDiff
{
    public function __construct(
        public readonly bool $negative,
        public readonly int $years,
        public readonly int $months,
        public readonly int $days,
        public readonly int $totalDays,
    ) {
    }
}
