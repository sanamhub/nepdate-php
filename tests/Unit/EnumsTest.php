<?php

declare(strict_types=1);

namespace NepDate\Tests\Unit;

use NepDate\DateOrder;
use NepDate\ErrorKind;
use NepDate\Lang;
use NepDate\Month;
use NepDate\MonthOverflow;
use NepDate\Quarter;
use NepDate\Separator;
use NepDate\Weekday;
use PHPUnit\Framework\TestCase;

/** The enum values of NF08 to NF10 and NY01, and the naming tables of FORMATTING.md §1. */
final class EnumsTest extends TestCase
{
    public function testEveryMonthHasAnEnglishAndANepaliName(): void
    {
        self::assertCount(12, Month::cases());
        foreach (Month::cases() as $month) {
            self::assertSame($month->value, $month->value);
            self::assertNotSame('', $month->label());
            self::assertNotSame('', $month->label(Lang::Ne));
            self::assertNotSame('', $month->shortLabel());
            self::assertSame($month->label(Lang::Ne), $month->shortLabel(Lang::Ne));
        }

        self::assertSame('Baishakh', Month::Baishakh->label());
        self::assertSame('Chaitra', Month::Chaitra->label());
        self::assertSame('Bai', Month::Baishakh->shortLabel());
        self::assertSame('बैशाख', Month::Baishakh->label(Lang::Ne));
    }

    public function testTheShortNamesFollowD04(): void
    {
        self::assertSame('Asa', Month::Ashad->shortLabel());
        self::assertSame('Aso', Month::Ashoj->shortLabel());
        self::assertSame(['Bai', 'Jes', 'Asa', 'Shr', 'Bha', 'Aso', 'Kar', 'Man', 'Pou', 'Mag', 'Fal', 'Cha'], array_map(
            static fn (Month $month): string => $month->shortLabel(),
            Month::cases(),
        ));
    }

    public function testEveryWeekdayHasANameInBothLanguages(): void
    {
        self::assertCount(7, Weekday::cases());
        self::assertSame(0, Weekday::Sunday->value);
        self::assertSame(6, Weekday::Saturday->value);
        self::assertSame('Sunday', Weekday::Sunday->label());
        self::assertSame('Saturday', Weekday::Saturday->label());
        self::assertSame('Sun', Weekday::Sunday->shortLabel());
        self::assertSame('आइतवार', Weekday::Sunday->label(Lang::Ne));
        self::assertSame('आइत', Weekday::Sunday->shortLabel(Lang::Ne));

        foreach (Weekday::cases() as $weekday) {
            self::assertNotSame('', $weekday->label());
            self::assertNotSame('', $weekday->label(Lang::Ne));
            self::assertNotSame('', $weekday->shortLabel());
            self::assertNotSame('', $weekday->shortLabel(Lang::Ne));
        }
    }

    public function testTheSixOrdersAndSixSeparators(): void
    {
        self::assertCount(6, DateOrder::cases());
        self::assertCount(6, Separator::cases());

        self::assertSame(['/', '\\', '.', '_', '-', ' '], array_map(
            static fn (Separator $separator): string => $separator->value,
            Separator::cases(),
        ));
    }

    public function testTheQuarterValuesAreTheFirstMonthOfTheQuarter(): void
    {
        self::assertSame([1, 2, 3, 4], array_map(
            static fn (Quarter $quarter): int => $quarter->value,
            Quarter::cases(),
        ));
    }

    public function testTheErrorKindsOfParsingSection4(): void
    {
        self::assertCount(9, ErrorKind::cases());
        self::assertSame('out_of_range', ErrorKind::OutOfRange->value);
        self::assertSame('invalid_character', ErrorKind::InvalidCharacter->value);
        self::assertSame('wrong_group_count', ErrorKind::WrongGroupCount->value);
        self::assertSame('number_too_long', ErrorKind::NumberTooLong->value);
        self::assertSame('ambiguous', ErrorKind::Ambiguous->value);
        self::assertSame('unrecognized', ErrorKind::Unrecognized->value);
    }

    public function testTheMonthOverflowModes(): void
    {
        self::assertSame(['Clamp', 'Spill'], array_map(
            static fn (MonthOverflow $mode): string => $mode->name,
            MonthOverflow::cases(),
        ));
    }

    public function testBothLanguagesAreDistinct(): void
    {
        self::assertNotSame(Month::Shrawan->label(Lang::En), Month::Shrawan->label(Lang::Ne));
        self::assertSame(Month::Shrawan->label(Lang::En), Month::Shrawan->label(Lang::En));
    }
}
