<?php

declare(strict_types=1);

namespace NepDate\Internal;

/**
 * The byte-level UTF-8 helpers the two parsers need (PARSING.md §1-2), written without
 * ext-mbstring so the library stays pure PHP (ADR-0001).
 *
 * A single byte is enough to recognise ASCII, and the lead byte gives the width of a multi-byte
 * character, so a scan needs no array of characters and no regular expression.
 *
 * @internal
 */
final class Utf8
{
    /** The whole character that starts at byte `$index`, or the remaining bytes if it is truncated. */
    public static function charAt(string $s, int $index): string
    {
        $lead = ord($s[$index]);
        $width = match (true) {
            $lead < 0xC0 => 1,
            $lead < 0xE0 => 2,
            $lead < 0xF0 => 3,
            default => 4,
        };

        return substr($s, $index, min($width, strlen($s) - $index));
    }

    /** The Unicode code point of the character `$char`, or -1 for an empty string. */
    public static function codepoint(string $char): int
    {
        $length = strlen($char);
        if ($length === 0) {
            return -1;
        }

        $first = ord($char[0]);
        if ($length === 1) {
            return $first;
        }
        if ($length === 2) {
            return (($first & 0x1F) << 6) | (ord($char[1]) & 0x3F);
        }
        if ($length === 3) {
            return (($first & 0x0F) << 12) | ((ord($char[1]) & 0x3F) << 6) | (ord($char[2]) & 0x3F);
        }

        return (($first & 0x07) << 18)
            | ((ord($char[1]) & 0x3F) << 12)
            | ((ord($char[2]) & 0x3F) << 6)
            | (ord($char[3]) & 0x3F);
    }

    /** The value of an ASCII or Devanagari digit (PARSING.md §1), or -1 for any other character. */
    public static function digitValue(string $char): int
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
}
