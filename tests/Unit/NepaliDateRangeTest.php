<?php

declare(strict_types=1);

namespace NepDate\Tests\Unit;

use DateTimeZone;
use NepDate\ErrorKind;
use NepDate\Lang;
use NepDate\NepaliDate;
use NepDate\NepaliDateRange;
use NepDate\NepDateException;
use PHPUnit\Framework\TestCase;

/** The range behaviour the vectors do not reach, and the argument checks of `NepaliDateRange`. */
final class NepaliDateRangeTest extends TestCase
{
    public function testConstructionAndCount(): void
    {
        $range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));

        self::assertSame('2081-04-10', $range->start()->isoformat());
        self::assertSame('2081-04-20', $range->end()->isoformat());
        self::assertSame(11, $range->count());
        self::assertSame(1, NepaliDateRange::singleDay(NepaliDate::fromBs(2081, 4, 15))->count());
        self::assertSame(5, NepaliDateRange::fromDayCount(NepaliDate::fromBs(2081, 4, 15), 5)->count());
        self::assertSame(32, NepaliDateRange::forMonth(2081, 4)->count());
        self::assertSame(366, NepaliDateRange::forYear(2081)->count());
    }

    public function testAnInvertedRangeAndAZeroDayCountAreRefused(): void
    {
        try {
            NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 20), NepaliDate::fromBs(2081, 4, 10));
            self::fail('start after end must be refused');
        } catch (NepDateException $error) {
            self::assertSame(ErrorKind::OutOfRange, $error->kind());
        }

        try {
            NepaliDateRange::fromDayCount(NepaliDate::fromBs(2081, 4, 15), 0);
            self::fail('a range must span at least one day');
        } catch (NepDateException $error) {
            self::assertSame(ErrorKind::OutOfRange, $error->kind());
        }
    }

    public function testEqualityIsValueEquality(): void
    {
        $a = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));
        $b = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));
        $c = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 21));

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testContainsRangeRequiresBothBounds(): void
    {
        $range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));

        self::assertTrue($range->containsRange(NepaliDateRange::of(
            NepaliDate::fromBs(2081, 4, 10),
            NepaliDate::fromBs(2081, 4, 20),
        )));
        self::assertTrue($range->containsRange(NepaliDateRange::singleDay(NepaliDate::fromBs(2081, 4, 15))));
        self::assertFalse($range->containsRange(NepaliDateRange::singleDay(NepaliDate::fromBs(2081, 4, 21))));
    }

    public function testTheIntervalHelperRejectsAStepBelowOne(): void
    {
        $range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));

        try {
            $range->datesWithInterval(0);
            self::fail('a step below one must be refused');
        } catch (NepDateException $error) {
            self::assertSame(ErrorKind::OutOfRange, $error->kind());
        }
    }

    public function testTheCurrentFactoriesAgreeWithTheExplicitOnes(): void
    {
        $zone = new DateTimeZone('Asia/Kathmandu');
        $today = NepaliDate::today($zone);

        self::assertTrue(NepaliDateRange::currentMonth($zone)->contains($today));
        self::assertTrue(NepaliDateRange::currentFiscalYear($zone)->contains($today));
        self::assertTrue(NepaliDateRange::currentCalendarYear($zone)->contains($today));

        $fiscal = NepaliDate::today($zone)->fiscalYear();
        self::assertSame(
            $fiscal->range()->start()->isoformat(),
            NepaliDateRange::currentFiscalYear($zone)->start()->isoformat(),
        );
    }

    public function testTheStringForms(): void
    {
        $range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));

        self::assertSame('2081/04/10 - 2081/04/20', (string) $range);
        self::assertSame('2081-04-10 - 2081-04-20', $range->toStringWith(\NepDate\DateOrder::Ymd, \NepDate\Separator::Dash));
        self::assertSame('२०८१/०४/१० - २०८१/०४/२०', $range->toStringWith(lang: Lang::Ne));
    }

    public function testTheRangeEndsDoNotThrowPerD16(): void
    {
        $tail = NepaliDateRange::of(NepaliDate::max(), NepaliDate::max());

        self::assertCount(1, iterator_to_array($tail, false));
        self::assertCount(1, $tail->datesWithInterval());
        self::assertCount(1, $tail->workingDays());
        self::assertSame(0, count($tail->weekendDays(false)));
        self::assertFalse($tail->isAdjacentTo(NepaliDateRange::singleDay(NepaliDate::fromBs(2081, 4, 10))));
        self::assertCount(1, $tail->splitByMonth());
        self::assertCount(1, $tail->splitByFiscalQuarter());
    }

    public function testRemovingTheWholeRangeLeavesNothing(): void
    {
        $month = NepaliDateRange::forMonth(2081, 4);

        self::assertSame([], $month->except($month));
        self::assertSame([$month], $month->except(NepaliDateRange::forMonth(2082, 4)));
        self::assertNull($month->intersect(NepaliDateRange::forMonth(2082, 4)));
    }
}
