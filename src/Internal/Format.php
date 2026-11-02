<?php

declare(strict_types=1);

namespace NepDate\Internal;

use NepDate\DateOrder;
use NepDate\Lang;
use NepDate\Separator;

/**
 * Formatting for NepaliDate: the default, ISO, short and long forms (FORMATTING.md §2-4).
 *
 * @internal
 */
final class Format
{
    public static function default(int $year, int $month, int $day, Lang $lang): string
    {
        $y = Names::yearDigits($year, $lang);
        $m = Names::dayDigits($month, true, $lang);
        $d = Names::dayDigits($day, true, $lang);

        return "{$y}/{$m}/{$d}";
    }

    public static function iso(int $year, int $month, int $day, Lang $lang): string
    {
        $y = Names::yearDigits($year, $lang);
        $m = Names::dayDigits($month, true, $lang);
        $d = Names::dayDigits($day, true, $lang);

        return "{$y}-{$m}-{$d}";
    }

    public static function short(
        int $year,
        int $month,
        int $day,
        DateOrder $order,
        Separator $separator,
        bool $pad,
        Lang $lang,
    ): string {
        $y = Names::yearDigits($year, $lang);
        $m = Names::dayDigits($month, $pad, $lang);
        $d = Names::dayDigits($day, $pad, $lang);
        $sep = $separator->value;

        return match ($order) {
            DateOrder::Ymd => "{$y}{$sep}{$m}{$sep}{$d}",
            DateOrder::Ydm => "{$y}{$sep}{$d}{$sep}{$m}",
            DateOrder::Myd => "{$m}{$sep}{$y}{$sep}{$d}",
            DateOrder::Mdy => "{$m}{$sep}{$d}{$sep}{$y}",
            DateOrder::Dym => "{$d}{$sep}{$y}{$sep}{$m}",
            DateOrder::Dmy => "{$d}{$sep}{$m}{$sep}{$y}",
        };
    }

    public static function long(
        int $year,
        int $month,
        int $day,
        ?int $weekday,
        bool $includeYear,
        bool $pad,
        Lang $lang,
    ): string {
        $y = Names::yearDigits($year, $lang);
        $d = Names::dayDigits($day, $pad, $lang);
        $langKey = $lang === Lang::Ne ? 'ne' : 'en';
        $monthName = Names::MONTH[$langKey][$month];
        $prefix = $weekday === null ? '' : Names::WEEKDAY[$langKey][$weekday] . ', ';
        $suffix = $includeYear ? ", {$y}" : '';

        return "{$prefix}{$monthName} {$d}{$suffix}";
    }
}
