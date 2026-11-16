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

    // Every number a date prints is below 100, or a year made of two such halves, so the
    // formatters read these tables instead of padding and translating on each call.

    /** @var list<string> `00` to `99`. */
    private const array TWO_DIGITS_EN = [
        '00', '01', '02', '03', '04', '05', '06', '07', '08', '09',
        '10', '11', '12', '13', '14', '15', '16', '17', '18', '19',
        '20', '21', '22', '23', '24', '25', '26', '27', '28', '29',
        '30', '31', '32', '33', '34', '35', '36', '37', '38', '39',
        '40', '41', '42', '43', '44', '45', '46', '47', '48', '49',
        '50', '51', '52', '53', '54', '55', '56', '57', '58', '59',
        '60', '61', '62', '63', '64', '65', '66', '67', '68', '69',
        '70', '71', '72', '73', '74', '75', '76', '77', '78', '79',
        '80', '81', '82', '83', '84', '85', '86', '87', '88', '89',
        '90', '91', '92', '93', '94', '95', '96', '97', '98', '99',
    ];

    /** @var list<string> `००` to `९९`. */
    private const array TWO_DIGITS_NE = [
        '००', '०१', '०२', '०३', '०४', '०५', '०६', '०७', '०८', '०९',
        '१०', '११', '१२', '१३', '१४', '१५', '१६', '१७', '१८', '१९',
        '२०', '२१', '२२', '२३', '२४', '२५', '२६', '२७', '२८', '२९',
        '३०', '३१', '३२', '३३', '३४', '३५', '३६', '३७', '३८', '३९',
        '४०', '४१', '४२', '४३', '४४', '४५', '४६', '४७', '४८', '४९',
        '५०', '५१', '५२', '५३', '५४', '५५', '५६', '५७', '५८', '५९',
        '६०', '६१', '६२', '६३', '६४', '६५', '६६', '६७', '६८', '६९',
        '७०', '७१', '७२', '७३', '७४', '७५', '७६', '७७', '७८', '७९',
        '८०', '८१', '८२', '८३', '८४', '८५', '८६', '८७', '८८', '८९',
        '९०', '९१', '९२', '९३', '९४', '९५', '९६', '९७', '९८', '९९',
    ];

    /** @var list<string> `०` to `९९`, not padded. */
    private const array PLAIN_NE = [
        '०', '१', '२', '३', '४', '५', '६', '७', '८', '९',
        '१०', '११', '१२', '१३', '१४', '१५', '१६', '१७', '१८', '१९',
        '२०', '२१', '२२', '२३', '२४', '२५', '२६', '२७', '२८', '२९',
        '३०', '३१', '३२', '३३', '३४', '३५', '३६', '३७', '३८', '३९',
        '४०', '४१', '४२', '४३', '४४', '४५', '४६', '४७', '४८', '४९',
        '५०', '५१', '५२', '५३', '५४', '५५', '५६', '५७', '५८', '५९',
        '६०', '६१', '६२', '६३', '६४', '६५', '६६', '६७', '६८', '६९',
        '७०', '७१', '७२', '७३', '७४', '७५', '७६', '७७', '७८', '७९',
        '८०', '८१', '८२', '८३', '८४', '८५', '८६', '८७', '८८', '८९',
        '९०', '९१', '९२', '९३', '९४', '९५', '९६', '९७', '९८', '९९',
    ];

    /** The digit string for `$n` (0..99) in `$lang`, zero-padded to two digits when `$pad`. */
    public static function dayDigits(int $n, bool $pad, \NepDate\Lang $lang): string
    {
        if ($lang === \NepDate\Lang::Ne) {
            return $pad ? self::TWO_DIGITS_NE[$n] : self::PLAIN_NE[$n];
        }

        return $pad ? self::TWO_DIGITS_EN[$n] : (string) $n;
    }

    /** The four-digit year string for `$year` (1000..9999) in `$lang`. */
    public static function yearDigits(int $year, \NepDate\Lang $lang): string
    {
        if ($lang === \NepDate\Lang::Ne) {
            return self::TWO_DIGITS_NE[(int) ($year / 100)] . self::TWO_DIGITS_NE[$year % 100];
        }

        return (string) $year;
    }

    /** The two-digit year string for `$year % 100` in `$lang`. */
    public static function year2Digits(int $yearMod100, \NepDate\Lang $lang): string
    {
        return $lang === \NepDate\Lang::Ne ? self::TWO_DIGITS_NE[$yearMod100] : self::TWO_DIGITS_EN[$yearMod100];
    }
}
