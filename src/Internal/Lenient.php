<?php

declare(strict_types=1);

namespace NepDate\Internal;

use NepDate\ErrorKind;
use NepDate\NepDateException;

/**
 * The lenient parser of PARSING.md §2.
 *
 * Input is matched as given, with no normalization and no joiner removal, so every port accepts
 * and rejects the same text.
 *
 * @internal
 */
final class Lenient
{
    private const string LENIENT_SEPARATORS = "-/\\_ |।,";
    private const int MAX_DIGITS = 4;
    private const string DEVA_DIGITS = '०१२३४५६७८९';
    private const string ASCII_DIGITS = '0123456789';

    private const array ERA_MARKERS = [
        'bs', 'b.s.', 'b.s', 'vs', 'v.s.', 'v.s',
        'बि.सं.', 'वि.सं.', 'बि.सं', 'वि.सं',
    ];

    private const array IGNORED_WORDS = ['गते', 'मिति'];

    /** @var list<array{0: string, 1: int}> */
    private const array MONTH_WORDS = [
        ['baishakh', 1], ['baisakh', 1], ['baishak', 1], ['vaishakh', 1], ['vaisakh', 1], ['bai', 1],
        ['बैशाख', 1], ['वैशाख', 1], ['बैसाख', 1],
        ['jestha', 2], ['jeth', 2], ['jyestha', 2], ['jes', 2],
        ['जेठ', 2], ['जेष्ठ', 2], ['ज्येष्ठ', 2],
        ['ashad', 3], ['ashadh', 3], ['asar', 3], ['asadh', 3], ['asa', 3],
        ['असार', 3], ['आषाढ', 3], ['असाढ', 3],
        ['shrawan', 4], ['shravan', 4], ['srawan', 4], ['sawan', 4], ['saun', 4], ['shr', 4],
        ['साउन', 4], ['श्रावण', 4], ['सावन', 4],
        ['bhadra', 5], ['bhadau', 5], ['bha', 5],
        ['भदौ', 5], ['भाद्र', 5], ['भाद्रपद', 5],
        ['ashoj', 6], ['asoj', 6], ['ashwin', 6], ['aso', 6],
        ['असोज', 6], ['आश्विन', 6], ['असौज', 6],
        ['kartik', 7], ['kattik', 7], ['kar', 7],
        ['कार्तिक', 7], ['कात्तिक', 7],
        ['mangsir', 8], ['mansir', 8], ['margashirsha', 8], ['man', 8],
        ['मंसिर', 8], ['मङ्सिर', 8], ['मार्गशीर्ष', 8],
        ['poush', 9], ['paush', 9], ['push', 9], ['pus', 9], ['pou', 9],
        ['पुष', 9], ['पौष', 9], ['पुस', 9],
        ['magh', 10], ['mag', 10], ['माघ', 10],
        ['falgun', 11], ['phalgun', 11], ['fagun', 11], ['fal', 11],
        ['फागुन', 11], ['फाल्गुन', 11],
        ['chaitra', 12], ['chait', 12], ['cha', 12],
        ['चैत', 12], ['चैत्र', 12],
    ];

    /**
     * @return array{0: int, 1: int, 2: int} the parsed year, month and day
     */
    public static function parse(string $s): array
    {
        try {
            return Parse::strict($s);
        } catch (NepDateException) {
            // fall through to the lenient scan
        }

        $normalized = strtr($s, self::DEVA_DIGITS, self::ASCII_DIGITS);
        [$numbers, $digitCounts, $months, $unknown] = self::tokens($normalized);

        if ($unknown) {
            throw new NepDateException(ErrorKind::Unrecognized, 'unknown word');
        }
        if (count($months) >= 2) {
            throw new NepDateException(ErrorKind::Unrecognized, 'more than one month name');
        }
        if (count($months) === 1 && count($numbers) === 2) {
            return self::monthForm($months[0], $numbers, $digitCounts);
        }
        if ($months === [] && count($numbers) === 3) {
            return self::numericForm($numbers, $digitCounts);
        }

        throw new NepDateException(ErrorKind::Unrecognized, 'unrecognized token count');
    }

    /**
     * @return array{0: list<int>, 1: list<int>, 2: list<int>, 3: bool}
     */
    private static function tokens(string $s): array
    {
        $numbers = [];
        $digitCounts = [];
        $months = [];
        $unknown = false;

        $index = 0;
        $length = strlen($s);
        while ($index < $length) {
            if (str_contains(self::LENIENT_SEPARATORS, $s[$index])) {
                $index++;
                continue;
            }
            $end = $index;
            while ($end < $length && !str_contains(self::LENIENT_SEPARATORS, $s[$end])) {
                $end++;
            }
            $chunk = substr($s, $index, $end - $index);
            $index = $end;

            if (self::isNumeric($chunk)) {
                $start = 0;
                $chunkLength = strlen($chunk);
                for ($position = 0; $position <= $chunkLength; $position++) {
                    if ($position === $chunkLength || $chunk[$position] === '.') {
                        $part = substr($chunk, $start, $position - $start);
                        if ($part !== '') {
                            if (strlen($part) > self::MAX_DIGITS) {
                                throw new NepDateException(
                                    ErrorKind::NumberTooLong,
                                    'a number has more than 4 digits',
                                );
                            }
                            $numbers[] = (int) $part;
                            $digitCounts[] = strlen($part);
                        }
                        $start = $position + 1;
                    }
                }
                continue;
            }

            $value = self::wordValue($chunk);
            if ($value > 0) {
                $months[] = $value;
            } elseif ($value < 0) {
                $unknown = true;
            }
        }

        return [$numbers, $digitCounts, $months, $unknown];
    }

    private static function isNumeric(string $chunk): bool
    {
        if ($chunk === '') {
            return false;
        }
        for ($i = 0, $len = strlen($chunk); $i < $len; $i++) {
            $c = $chunk[$i];
            if (($c < '0' || $c > '9') && $c !== '.') {
                return false;
            }
        }

        return true;
    }

    /** A month number, 0 for a dropped word, or -1 for an unknown word. */
    private static function wordValue(string $token): int
    {
        $folded = strtolower($token);
        foreach (self::MONTH_WORDS as [$name, $month]) {
            if ($folded === $name) {
                return $month;
            }
        }
        if (in_array($folded, self::ERA_MARKERS, true) || in_array($folded, self::IGNORED_WORDS, true)) {
            return 0;
        }

        return -1;
    }

    /**
     * @param list<int> $numbers
     * @param list<int> $digitCounts
     * @return array{0: int, 1: int, 2: int}
     */
    private static function monthForm(int $month, array $numbers, array $digitCounts): array
    {
        $four = [];
        foreach ($digitCounts as $i => $count) {
            if ($count === self::MAX_DIGITS) {
                $four[] = $i;
            }
        }
        if (count($four) !== 1) {
            throw new NepDateException(ErrorKind::Ambiguous, 'no single four-digit year');
        }
        $yearIndex = $four[0];
        $otherIndex = 1 - $yearIndex;
        if ($digitCounts[$otherIndex] > 2) {
            throw new NepDateException(ErrorKind::Ambiguous, 'the day has more than two digits');
        }

        return [$numbers[$yearIndex], $month, $numbers[$otherIndex]];
    }

    /**
     * @param list<int> $numbers
     * @param list<int> $digitCounts
     * @return array{0: int, 1: int, 2: int}
     */
    private static function numericForm(array $numbers, array $digitCounts): array
    {
        if ($digitCounts[0] === self::MAX_DIGITS) {
            return [$numbers[0], $numbers[1], $numbers[2]];
        }
        if ($digitCounts[2] === self::MAX_DIGITS) {
            return [$numbers[2], $numbers[1], $numbers[0]];
        }

        throw new NepDateException(ErrorKind::Ambiguous, 'no four-digit year first or last');
    }
}
