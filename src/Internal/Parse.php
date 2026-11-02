<?php

declare(strict_types=1);

namespace NepDate\Internal;

use NepDate\ErrorKind;
use NepDate\NepDateException;

/**
 * The strict parser of PARSING.md §1: a hand-written scanner, no regex.
 *
 * @internal
 */
final class Parse
{
    private const int GROUP_COUNT = 3;
    private const int MAX_DIGITS = 4;
    private const string SEPARATORS = "-/._\\ ।|";

    /**
     * @return array{0: int, 1: int, 2: int} the parsed year, month and day
     */
    public static function strict(string $s): array
    {
        $groups = [0, 0, 0];
        $count = 0;
        $inGroup = false;
        $digits = 0;

        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            throw new NepDateException(ErrorKind::InvalidCharacter, 'input is not valid UTF-8');
        }

        foreach ($chars as $char) {
            $value = self::digitValue($char);
            if ($value >= 0) {
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
                $groups[$count - 1] = $groups[$count - 1] * 10 + $value;
            } elseif (str_contains(self::SEPARATORS, $char)) {
                $inGroup = false;
            } else {
                throw new NepDateException(ErrorKind::InvalidCharacter, "invalid character '{$char}'");
            }
        }

        if ($count < self::GROUP_COUNT) {
            throw new NepDateException(ErrorKind::WrongGroupCount, 'fewer than three groups');
        }

        return [$groups[0], $groups[1], $groups[2]];
    }

    /** The value of an ASCII or Devanagari digit, or -1 for any other character. */
    private static function digitValue(string $char): int
    {
        $code = self::codepoint($char);
        if ($code >= 0x30 && $code <= 0x39) {
            return $code - 0x30;
        }
        if ($code >= 0x0966 && $code <= 0x096F) {
            return $code - 0x0966;
        }

        return -1;
    }

    /** Decodes the single UTF-8 character `$char` to its Unicode code point, without ext-mbstring. */
    private static function codepoint(string $char): int
    {
        $len = strlen($char);
        if ($len === 0) {
            return -1;
        }
        $first = ord($char[0]);
        if ($len === 1) {
            return $first;
        }
        if ($len === 2) {
            return (($first & 0x1F) << 6) | (ord($char[1]) & 0x3F);
        }
        if ($len === 3) {
            return (($first & 0x0F) << 12) | ((ord($char[1]) & 0x3F) << 6) | (ord($char[2]) & 0x3F);
        }

        return (($first & 0x07) << 18)
            | ((ord($char[1]) & 0x3F) << 12)
            | ((ord($char[2]) & 0x3F) << 6)
            | (ord($char[3]) & 0x3F);
    }
}
