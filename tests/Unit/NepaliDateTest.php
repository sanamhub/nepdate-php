<?php

declare(strict_types=1);

namespace NepDate\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use NepDate\DateDiff;
use NepDate\ErrorKind;
use NepDate\Lang;
use NepDate\Month;
use NepDate\MonthOverflow;
use NepDate\NepaliDate;
use NepDate\NepDateException;
use NepDate\Quarter;
use NepDate\Weekday;
use PHPUnit\Framework\TestCase;

/**
 * The rows of `API.md` that no golden vector covers: validation order, the clock methods, the
 * batch and integer forms, and the behaviour the spec states in prose.
 */
final class NepaliDateTest extends TestCase
{
    public function testValidationReportsTheYearFirstThenTheMonthThenTheDay(): void
    {
        self::assertSame(ErrorKind::OutOfRange, self::kindOf(1900, 13, 40));
        self::assertSame(ErrorKind::OutOfRange, self::kindOf(2200, 0, 0));
        self::assertSame(ErrorKind::InvalidMonth, self::kindOf(2081, 13, 40));
        self::assertSame(ErrorKind::InvalidMonth, self::kindOf(2081, 0, 1));
        self::assertSame(ErrorKind::InvalidDay, self::kindOf(2081, 4, 0));
        self::assertSame(ErrorKind::InvalidDay, self::kindOf(2199, 12, 31));
    }

    public function testTheRangeEndsAreTheDocumentedDates(): void
    {
        self::assertSame('1901-01-01', NepaliDate::min()->isoformat());
        self::assertSame('1844-04-11', NepaliDate::min()->toDateTime()->format('Y-m-d'));
        self::assertSame('2199-12-30', NepaliDate::max()->isoformat());
        self::assertSame('2143-04-15', NepaliDate::max()->toDateTime()->format('Y-m-d'));
    }

    public function testAGregorianDateOutsideTheSpanIsRejected(): void
    {
        foreach (['1844-04-10', '2143-04-16'] as $text) {
            try {
                NepaliDate::fromGregorian(new DateTimeImmutable($text));
                self::fail("{$text} should be outside the supported span");
            } catch (NepDateException $error) {
                self::assertSame(ErrorKind::OutOfRange, $error->kind());
            }
        }
    }

    public function testANonMidnightGregorianTimeIsDiscarded(): void
    {
        $date = NepaliDate::fromGregorian(new DateTimeImmutable('2024-07-30 23:59:59'));

        self::assertSame('2081-04-15', $date->isoformat());
        self::assertSame('00:00:00', $date->toDateTime()->format('H:i:s'));
    }

    public function testTheAccessors(): void
    {
        $date = NepaliDate::fromBs(2081, 4, 15);

        self::assertSame(2081, $date->year());
        self::assertSame(4, $date->month());
        self::assertSame(15, $date->day());
        self::assertSame(Month::Shrawan, $date->monthEnum());
        self::assertSame(Weekday::Tuesday, $date->weekday());
        self::assertSame(109, $date->dayOfYear());
        self::assertSame(32, $date->monthLength());
        self::assertSame(366, $date->yearLength());
        self::assertSame([2081, 4, 15], $date->toArray());
        self::assertSame($date->serial(), NepaliDate::fromGregorian($date->toDateTime())->serial());
    }

    public function testTheLastDayOfTheMonth(): void
    {
        self::assertSame('2081-04-32', NepaliDate::fromBs(2081, 4, 15)->lastDayOfMonth()->isoformat());
        self::assertSame('2081-04-32', NepaliDate::fromBs(2081, 4, 32)->lastDayOfMonth()->isoformat());
        self::assertSame('2199-12-30', NepaliDate::max()->lastDayOfMonth()->isoformat());
    }

    public function testTheClockMethodsUseTheGivenZone(): void
    {
        $zone = new DateTimeZone('Asia/Kathmandu');
        $today = NepaliDate::today($zone);

        self::assertTrue($today->isToday($zone));
        self::assertTrue($today->addDays(-1)->isYesterday($zone));
        self::assertTrue($today->addDays(1)->isTomorrow($zone));
        self::assertFalse($today->addDays(-2)->isYesterday($zone));
        self::assertFalse($today->addDays(2)->isTomorrow($zone));
        self::assertFalse(NepaliDate::min()->isToday($zone));

        // A zone 12 hours behind Kathmandu can still hold the previous date.
        $other = new DateTimeZone('Pacific/Auckland');
        self::assertTrue(NepaliDate::today($other)->isToday($other));
    }

    public function testComparisonAndEquality(): void
    {
        $early = NepaliDate::fromBs(2081, 4, 10);
        $late = NepaliDate::fromBs(2081, 4, 20);

        self::assertSame(-1, $early->compareTo($late));
        self::assertSame(1, $late->compareTo($early));
        self::assertSame(0, $early->compareTo(NepaliDate::fromBs(2081, 4, 10)));
        self::assertTrue($early->equals(NepaliDate::fromBs(2081, 4, 10)));
        self::assertFalse($early->equals($late));
    }

    public function testAddMonthsAndYearsShareTheOverflowMode(): void
    {
        $date = NepaliDate::fromBs(2081, 4, 32);

        self::assertSame('2081-06-30', $date->addMonths(2)->isoformat());
        self::assertSame('2081-07-02', $date->addMonths(2, MonthOverflow::Spill)->isoformat());
        self::assertSame('2081-02-32', $date->addMonths(-2)->isoformat());
        self::assertTrue($date->addYears(1)->equals($date->addMonths(12)));
        self::assertTrue(
            $date->addYears(1, MonthOverflow::Spill)->equals($date->addMonths(12, MonthOverflow::Spill)),
        );
    }

    public function testTheDayDifferenceSignsOfD07(): void
    {
        $a = NepaliDate::fromBs(2080, 1, 1);
        $b = NepaliDate::fromBs(2081, 4, 15);

        self::assertSame(473, $a->daysUntil($b));
        self::assertSame(-473, $b->daysUntil($a));
        self::assertSame(473, $a->diff($b)->totalDays);
        self::assertSame(-473, $b->diff($a)->totalDays);
    }

    public function testTheCalendarBreakdown(): void
    {
        $diff = NepaliDate::fromBs(2080, 1, 1)->diff(NepaliDate::fromBs(2081, 4, 15));

        self::assertInstanceOf(DateDiff::class, $diff);
        self::assertFalse($diff->negative);
        self::assertSame(1, $diff->years);
        self::assertSame(3, $diff->months);
        self::assertSame(14, $diff->days);
        self::assertSame(473, $diff->totalDays);

        $back = NepaliDate::fromBs(2081, 4, 15)->diff(NepaliDate::fromBs(2080, 1, 1));
        self::assertTrue($back->negative);
        self::assertSame(1, $back->years);
        self::assertSame(3, $back->months);
        self::assertSame(14, $back->days);
        self::assertSame(-473, $back->totalDays);

        $same = NepaliDate::fromBs(2081, 3, 15)->diff(NepaliDate::fromBs(2081, 3, 15));
        self::assertFalse($same->negative);
        self::assertSame(0, $same->totalDays);
    }

    public function testTheFiscalYearAndQuarterOfADate(): void
    {
        $date = NepaliDate::fromBs(2081, 4, 15);

        self::assertSame(2081, $date->fiscalYear()->startYear);
        self::assertSame(Quarter::Q1, $date->quarter());
        self::assertSame(Quarter::Q2, NepaliDate::fromBs(2081, 7, 1)->quarter());
        self::assertSame(Quarter::Q3, NepaliDate::fromBs(2081, 10, 1)->quarter());
        self::assertSame(Quarter::Q4, NepaliDate::fromBs(2081, 1, 1)->quarter());
        self::assertSame(2080, NepaliDate::fromBs(2081, 1, 1)->fiscalYear()->startYear);
    }

    public function testSerialRoundTripAndArithmeticRangeChecks(): void
    {
        $date = NepaliDate::fromBs(2081, 4, 15);
        self::assertSame($date->serial(), NepaliDate::fromGregorian($date->toDateTime())->serial());
        self::assertSame(0, NepaliDate::min()->serial());
        self::assertSame(109211, NepaliDate::max()->serial());

        try {
            NepaliDate::min()->addDays(-1);
            self::fail('the range ends must be enforced');
        } catch (NepDateException $error) {
            self::assertSame(ErrorKind::OutOfRange, $error->kind());
        }
        try {
            NepaliDate::max()->addDays(1);
            self::fail('the range ends must be enforced');
        } catch (NepDateException $error) {
            self::assertSame(ErrorKind::OutOfRange, $error->kind());
        }
        try {
            NepaliDate::max()->addMonths(1);
            self::fail('the range ends must be enforced');
        } catch (NepDateException $error) {
            self::assertSame(ErrorKind::OutOfRange, $error->kind());
        }
    }

    public function testTheLongFormOptions(): void
    {
        $date = NepaliDate::fromBs(2079, 2, 6);

        self::assertSame('Jestha 06, 2079', $date->toLongString());
        self::assertSame('Jestha 6, 2079', $date->toLongString(pad: false));
        self::assertSame('Friday, Jestha 6', $date->toLongString(weekday: true, year: false, pad: false));
        self::assertSame('शुक्रवार, जेठ ६', $date->toLongString(weekday: true, year: false, pad: false, lang: Lang::Ne));
    }

    public function testTheDefaultAndStandardStringForms(): void
    {
        $date = NepaliDate::fromBs(2081, 4, 15);

        self::assertSame('2081/04/15', (string) $date);
        self::assertSame('2081/04/15', $date->format(''));
        self::assertSame('2081/04/15', $date->format('G'));
        self::assertSame('2081/04/15', $date->format('g'));
        self::assertSame('2081/04/15', $date->format('d'));
        self::assertSame('Shrawan 15, 2081', $date->format('D'));
        self::assertSame('2081-04-15', $date->format('s'));
        self::assertSame('२०८१/०४/१५', $date->format('', Lang::Ne));
        self::assertSame('२०८१-०४-१५', $date->format('s', Lang::Ne));
    }

    public function testTheBatchConversionsKeepInputOrder(): void
    {
        $dates = [
            new DateTimeImmutable('1844-04-11'),
            new DateTimeImmutable('2024-07-31'),
            new DateTimeImmutable('2143-04-15'),
        ];
        $converted = NepaliDate::fromGregorianBatch($dates);

        self::assertSame(
            ['1901-01-01', '2081-04-16', '2199-12-30'],
            array_map(static fn (NepaliDate $date): string => $date->isoformat(), $converted),
        );
        self::assertSame(
            ['1844-04-11', '2024-07-31', '2143-04-15'],
            array_map(
                static fn (DateTimeImmutable $date): string => $date->format('Y-m-d'),
                NepaliDate::toGregorianBatch($converted),
            ),
        );
        self::assertSame([], NepaliDate::fromGregorianBatch([]));
        self::assertSame([], NepaliDate::parseBatch([]));
        self::assertSame([], NepaliDate::toGregorianBatch([]));
    }

    public function testTheIntegerFormRejectsAnImpossibleDate(): void
    {
        try {
            NepaliDate::fromYmd(20810433);
            self::fail('2081-04-33 is not a date');
        } catch (NepDateException $error) {
            self::assertSame(ErrorKind::InvalidDay, $error->kind());
        }
    }

    public function testTheExceptionIsARuntimeExceptionWithAKind(): void
    {
        try {
            NepaliDate::parse('not a date');
            self::fail('the text is not a date');
        } catch (NepDateException $error) {
            self::assertSame(ErrorKind::InvalidCharacter, $error->kind());
            self::assertNotSame('', $error->getMessage());
            self::assertInstanceOf(\RuntimeException::class, $error);
        }
    }

    private static function kindOf(int $year, int $month, int $day): ErrorKind
    {
        try {
            NepaliDate::fromBs($year, $month, $day);
        } catch (NepDateException $error) {
            return $error->kind();
        }

        self::fail("{$year}-{$month}-{$day} should not be a valid date");
    }
}
