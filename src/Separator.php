<?php

declare(strict_types=1);

namespace NepDate;

/**
 * The separator of {@see NepaliDate::toShortString()} (FORMATTING.md §3).
 */
enum Separator: string
{
    case Slash = '/';
    case Backslash = '\\';
    case Dot = '.';
    case Underscore = '_';
    case Dash = '-';
    case Space = ' ';
}
