<?php

declare(strict_types=1);

namespace NepDate;

/**
 * Which validation rule a {@see NepDateException} failed, for programmatic matching.
 */
enum ErrorKind: string
{
    case OutOfRange = 'out_of_range';
    case InvalidMonth = 'invalid_month';
    case InvalidDay = 'invalid_day';
    case InvalidGregorian = 'invalid_gregorian';
    case InvalidCharacter = 'invalid_character';
    case WrongGroupCount = 'wrong_group_count';
    case NumberTooLong = 'number_too_long';
    case Ambiguous = 'ambiguous';
    case Unrecognized = 'unrecognized';
}
