<?php

declare(strict_types=1);

namespace NepDate;

/**
 * The field order of {@see NepaliDate::toShortString()} (FORMATTING.md §3).
 */
enum DateOrder
{
    case Ymd;
    case Ydm;
    case Myd;
    case Mdy;
    case Dym;
    case Dmy;
}
