<?php

declare(strict_types=1);

namespace NepDate\Internal;

/**
 * Month, weekday and digit tables (FORMATTING.md §1). Index 0 of each month/day array is unused
 * padding so a 1-based BS month or day number indexes its name or digit string directly.
 *
 * @internal
 */
final class Names
{
    /** @var array{en: list<string>, ne: list<string>} */
    public const array MONTH = [
        'en' => [
            '', 'Baishakh', 'Jestha', 'Ashad', 'Shrawan', 'Bhadra', 'Ashoj',
            'Kartik', 'Mangsir', 'Poush', 'Magh', 'Falgun', 'Chaitra',
        ],
        'ne' => [
            '', 'बैशाख', 'जेठ', 'असार', 'साउन', 'भदौ', 'असोज',
            'कार्तिक', 'मंसिर', 'पुष', 'माघ', 'फागुन', 'चैत',
        ],
    ];

    // D-04: Ashad and Ashoj use Asa and Aso instead of the C# "Ash" for both.
    /** @var list<string> */
    public const array MONTH_SHORT_EN = [
        '', 'Bai', 'Jes', 'Asa', 'Shr', 'Bha', 'Aso',
        'Kar', 'Man', 'Pou', 'Mag', 'Fal', 'Cha',
    ];

    /** @var array{en: list<string>, ne: list<string>} */
    public const array WEEKDAY = [
        'en' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'ne' => ['आइतवार', 'सोमवार', 'मङ्गलवार', 'बुधवार', 'बिहिवार', 'शुक्रवार', 'शनिवार'],
    ];

    /** @var array{en: list<string>, ne: list<string>} */
    public const array WEEKDAY_SHORT = [
        'en' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        'ne' => ['आइत', 'सोम', 'मङ्गल', 'बुध', 'बिहि', 'शुक्र', 'शनि'],
    ];

    private const array DIGIT_MAP = [
        '0' => '०', '1' => '१', '2' => '२', '3' => '३', '4' => '४',
        '5' => '५', '6' => '६', '7' => '७', '8' => '८', '9' => '९',
    ];

    /** Format `$n` in ASCII, zero-padded to `$minWidth`, then map the digits to Devanagari. */
    public static function toNepaliDigits(int $n, int $minWidth = 0): string
    {
        $ascii = str_pad((string) $n, $minWidth, '0', STR_PAD_LEFT);

        return strtr($ascii, self::DIGIT_MAP);
    }

    /** The digit string for `$n` (0..32) in `$lang`, zero-padded to two digits when `$pad`. */
    public static function dayDigits(int $n, bool $pad, \NepDate\Lang $lang): string
    {
        $text = $pad ? str_pad((string) $n, 2, '0', STR_PAD_LEFT) : (string) $n;

        return $lang === \NepDate\Lang::Ne ? strtr($text, self::DIGIT_MAP) : $text;
    }

    /** The four-digit year string for `$year` in `$lang`. */
    public static function yearDigits(int $year, \NepDate\Lang $lang): string
    {
        $text = (string) $year;

        return $lang === \NepDate\Lang::Ne ? strtr($text, self::DIGIT_MAP) : $text;
    }

    /** The two-digit year string for `$year % 100` in `$lang`. */
    public static function year2Digits(int $yearMod100, \NepDate\Lang $lang): string
    {
        $text = str_pad((string) $yearMod100, 2, '0', STR_PAD_LEFT);

        return $lang === \NepDate\Lang::Ne ? strtr($text, self::DIGIT_MAP) : $text;
    }
}
