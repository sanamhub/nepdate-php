<?php

declare(strict_types=1);

namespace NepDate\Internal;

use NepDate\Lang;

/**
 * The hand-written pattern formatter of FORMATTING.md §5: one left-to-right pass, no regex.
 *
 * @internal
 */
final class Pattern
{
    private const array STANDARD_DEFAULT = ['', 'G', 'g', 'd'];

    public static function format(string $pattern, int $year, int $month, int $day, Lang $lang): string
    {
        if (in_array($pattern, self::STANDARD_DEFAULT, true)) {
            return Format::default($year, $month, $day, $lang);
        }
        if ($pattern === 'D') {
            return Format::long($year, $month, $day, null, true, true, $lang);
        }
        if ($pattern === 's') {
            return Format::iso($year, $month, $day, $lang);
        }

        $langKey = $lang === Lang::Ne ? 'ne' : 'en';
        $yearText = Names::yearDigits($year, $lang);
        $year2Text = Names::year2Digits($year % 100, $lang);
        $monthName = Names::MONTH[$langKey][$month];
        $monthShort = $lang === Lang::Ne ? Names::MONTH[$langKey][$month] : Names::MONTH_SHORT_EN[$month];
        $monthPad = Names::dayDigits($month, true, $lang);
        $monthPlain = Names::dayDigits($month, false, $lang);
        $dayPad = Names::dayDigits($day, true, $lang);
        $dayPlain = Names::dayDigits($day, false, $lang);

        $out = '';
        $index = 0;
        $length = strlen($pattern);
        while ($index < $length) {
            $char = $pattern[$index];
            if ($char === '\\') {
                if ($index + 1 < $length) {
                    $out .= $pattern[$index + 1];
                }
                $index += 2;
                continue;
            }
            if ($char === "'") {
                $end = strpos($pattern, "'", $index + 1);
                if ($end === false) {
                    $out .= substr($pattern, $index + 1);
                    break;
                }
                $out .= substr($pattern, $index + 1, $end - $index - 1);
                $index = $end + 1;
                continue;
            }
            if ($char === 'y') {
                $end = $index + 1;
                while ($end < $length && $pattern[$end] === 'y') {
                    $end++;
                }
                $out .= ($end - $index >= 4) ? $yearText : $year2Text;
                $index = $end;
                continue;
            }
            if ($char === 'M') {
                $end = $index + 1;
                while ($end < $length && $pattern[$end] === 'M') {
                    $end++;
                }
                $run = $end - $index;
                $out .= match (true) {
                    $run >= 4 => $monthName,
                    $run === 3 => $monthShort,
                    $run === 2 => $monthPad,
                    default => $monthPlain,
                };
                $index = $end;
                continue;
            }
            if ($char === 'd') {
                $end = $index + 1;
                while ($end < $length && $pattern[$end] === 'd') {
                    $end++;
                }
                $out .= ($end - $index === 2) ? $dayPad : $dayPlain;
                $index = $end;
                continue;
            }
            $out .= $char;
            $index++;
        }

        return $out;
    }
}
