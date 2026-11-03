<?php

declare(strict_types=1);

namespace NepDate\Internal;

use NepDate\ErrorKind;
use NepDate\NepDateException;

/**
 * The strict parser of PARSING.md §1: a hand-written, allocation-free scanner, no regex.
 *
 * The scanner walks bytes. An ASCII byte is a digit or a separator in one comparison, and only a
 * byte at or above 0x80 costs a character read, which is what `YYYY/MM/DD` (the common input) and
 * Devanagari digits need.
 *
 * @internal
 */
final class Parse
{
    private const int GROUP_COUNT = 3;
    private const int MAX_DIGITS = 4;

    /** The ASCII separators of PARSING.md §1, by byte: - / . _ \ space | */
    private const array ASCII_SEPARATORS = [
        0x2D => true,
        0x2F => true,
        0x2E => true,
        0x5F => true,
        0x5C => true,
        0x20 => true,
        0x7C => true,
    ];

    /** The multi-byte separators of PARSING.md §1: `।` (U+0964). */
    private const string MULTI_BYTE_SEPARATORS = '।';

    /**
     * @return array{0: int, 1: int, 2: int} the parsed year, month and day
     */
    public static function strict(string $s): array
    {
        $groups = [0, 0, 0];
        $count = 0;
        $inGroup = false;
        $digits = 0;

        $index = 0;
        $length = strlen($s);
        while ($index < $length) {
            $ord = ord($s[$index]);
            $isDigit = $ord >= 0x30 && $ord <= 0x39;
            $isSeparator = isset(self::ASCII_SEPARATORS[$ord]);
            $width = 1;

            if (!$isDigit && !$isSeparator) {
                $char = Utf8::charAt($s, $index);
                $width = strlen($char);
                $value = Utf8::digitValue($char);
                if ($value >= 0) {
                    $isDigit = true;
                    $ord = 0x30 + $value;
                } elseif (str_contains(self::MULTI_BYTE_SEPARATORS, $char)) {
                    $isSeparator = true;
                } else {
                    throw new NepDateException(ErrorKind::InvalidCharacter, "invalid character '{$char}'");
                }
            }

            if ($isDigit) {
                if (!$inGroup) {
                    if ($count === self::GROUP_COUNT) {
                        throw new NepDateException(ErrorKind::WrongGroupCount, 'more than three groups');
                    }
                    $count++;
                    $inGroup = true;
                    $digits = 0;
                }
                $digits++;
                if ($digits > self::MAX_DIGITS) {
                    throw new NepDateException(ErrorKind::NumberTooLong, 'a group has more than 4 digits');
                }
                $groups[$count - 1] = $groups[$count - 1] * 10 + ($ord - 0x30);
                $index += $width;
                continue;
            }

            $inGroup = false;
            $index += $width;
        }

        if ($count < self::GROUP_COUNT) {
            throw new NepDateException(ErrorKind::WrongGroupCount, 'fewer than three groups');
        }

        return [$groups[0], $groups[1], $groups[2]];
    }
}
