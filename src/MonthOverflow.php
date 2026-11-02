<?php

declare(strict_types=1);

namespace NepDate;

/**
 * How {@see NepaliDate::addMonths()} and {@see NepaliDate::addYears()} handle a day past the
 * target month's end (ALGORITHM.md §8, D-02).
 */
enum MonthOverflow
{
    /** Cap the day at the last day of the target month. */
    case Clamp;

    /** Carry the excess days into the following month. */
    case Spill;
}
