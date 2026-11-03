<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use DateTimeImmutable;
use NepDate\NepaliDate;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\TestCase;

/**
 * month-boundaries.csv (ND01, ND04, ND10, ND12): the first and last day of all 7176 BS months of
 * the data range, with the Gregorian date, weekday and month length of each.
 *
 * The file is comma separated, unlike every other vector file.
 */
final class MonthBoundariesTest extends TestCase
{
    public function testEveryMonthBoundary(): void
    {
        $rows = Vectors::load('month-boundaries.csv', ',');
        self::assertCount(7176, $rows);

        foreach ($rows as $row) {
            $bs = $row['bs'];
            $date = NepaliDate::parse($bs);
            self::assertSame($row['ad'], $date->toDateTime()->format('Y-m-d'), $bs);
            self::assertSame(
                $bs,
                NepaliDate::fromGregorian(new DateTimeImmutable($row['ad']))->isoformat(),
                $row['ad'],
            );
            self::assertSame($row['weekday'], $date->weekday()->name, $bs);
            self::assertSame((int) $row['month_length'], $date->monthLength(), $bs);
        }
    }

    public function testEveryRowIsAFirstOrLastDayOfItsMonth(): void
    {
        foreach (Vectors::load('month-boundaries.csv', ',') as $row) {
            $date = NepaliDate::parse($row['bs']);
            $first = NepaliDate::fromBs($date->year(), $date->month(), 1);
            $last = $first->lastDayOfMonth();

            self::assertSame((int) $row['month_length'], $last->day(), $row['bs']);
            self::assertTrue($first->equals($date) || $last->equals($date), $row['bs']);
        }
    }

    public function testEverySerialRoundTripsThroughGregorian(): void
    {
        $date = NepaliDate::min();
        $count = 0;
        while (true) {
            self::assertTrue(
                $date->equals(NepaliDate::fromGregorian($date->toDateTime())),
                $date->isoformat(),
            );
            self::assertSame($count, NepaliDate::min()->daysUntil($date));
            $count++;
            if ($date->equals(NepaliDate::max())) {
                break;
            }
            $date = $date->addDays(1);
        }

        self::assertSame(109212, $count);
    }
}
