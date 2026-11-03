<?php

declare(strict_types=1);

namespace NepDate\Tests\Unit;

use NepDate\FiscalYear;
use NepDate\Lang;
use NepDate\NepaliDate;
use NepDate\NepDateException;
use NepDate\Quarter;
use PHPUnit\Framework\TestCase;

/** The fiscal-year value of NY01 to NY10: containing, start, end, range, quarters and label. */
final class FiscalYearTest extends TestCase
{
    public function testTheContainingFiscalYear(): void
    {
        self::assertSame(2081, FiscalYear::containing(NepaliDate::fromBs(2081, 4, 1))->startYear);
        self::assertSame(2081, FiscalYear::containing(NepaliDate::fromBs(2081, 12, 30))->startYear);
        self::assertSame(2080, FiscalYear::containing(NepaliDate::fromBs(2081, 1, 1))->startYear);
        self::assertSame(2080, FiscalYear::containing(NepaliDate::fromBs(2081, 3, 31))->startYear);
    }

    public function testStartEndAndRange(): void
    {
        $fiscal = new FiscalYear(2081);

        self::assertSame('2081-04-01', $fiscal->start()->isoformat());
        self::assertSame('2082-03-32', $fiscal->end()->isoformat());
        self::assertTrue($fiscal->range()->start()->equals($fiscal->start()));
        self::assertTrue($fiscal->range()->end()->equals($fiscal->end()));
        self::assertTrue($fiscal->range()->contains(NepaliDate::fromBs(2081, 4, 15)));
        self::assertTrue($fiscal->range()->contains(NepaliDate::fromBs(2082, 3, 1)));
        self::assertFalse($fiscal->range()->contains(NepaliDate::fromBs(2081, 1, 1)));
    }

    public function testTheFourQuartersCoverTheYearInOrder(): void
    {
        $fiscal = new FiscalYear(2081);
        $previousEnd = null;

        foreach ([Quarter::Q1, Quarter::Q2, Quarter::Q3, Quarter::Q4] as $quarter) {
            $range = $fiscal->quarterRange($quarter);
            self::assertTrue($fiscal->range()->contains($range->start()), $quarter->name);
            self::assertTrue($fiscal->range()->contains($range->end()), $quarter->name);
            if ($previousEnd !== null) {
                self::assertTrue($previousEnd->isAdjacentTo($range), $quarter->name);
            }
            $previousEnd = $range;
        }

        self::assertSame('2081-04-01', $fiscal->quarterRange(Quarter::Q1)->start()->isoformat());
        self::assertSame('2081-06-30', $fiscal->quarterRange(Quarter::Q1)->end()->isoformat());
        self::assertSame('2081-07-01', $fiscal->quarterRange(Quarter::Q2)->start()->isoformat());
        self::assertSame('2082-03-32', $fiscal->quarterRange(Quarter::Q4)->end()->isoformat());
    }

    public function testTheLabel(): void
    {
        self::assertSame('2081/82', (new FiscalYear(2081))->label());
        self::assertSame('2081/82', (string) new FiscalYear(2081));
        self::assertSame('२०८१/८२', (new FiscalYear(2081))->label(Lang::Ne));
        self::assertSame('2099/00', (new FiscalYear(2099))->label());
    }

    public function testEquality(): void
    {
        self::assertTrue((new FiscalYear(2081))->equals(new FiscalYear(2081)));
        self::assertFalse((new FiscalYear(2081))->equals(new FiscalYear(2082)));
    }

    public function testAYearPastTheDataEndIsRejected(): void
    {
        $this->expectException(NepDateException::class);
        (new FiscalYear(2199))->end();
    }
}
