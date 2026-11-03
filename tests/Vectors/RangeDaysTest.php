<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDateRange;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/range-days.tsv (NR24-NR27): the interval, working-day and weekend-day helpers
 * and the enumerator, including the D-16 rows that reach 2199-12-30 without throwing.
 */
final class RangeDaysTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/range-days.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        $start = $row['start'];
        $end = $row['end'];
        $argument = $row['argument'];
        $range = static fn (): NepaliDateRange => NepaliDateRange::of(Outcome::bs($start), Outcome::bs($end));

        $actual = match ($row['operation']) {
            'dates_with_interval' => Outcome::dateList(
                static fn (): array => $range()->datesWithInterval((int) $argument),
            ),
            'working_days' => Outcome::dateList(
                static fn (): array => $range()->workingDays($argument === 'true'),
            ),
            'weekend_days' => Outcome::dateList(
                static fn (): array => $range()->weekendDays($argument === 'true'),
            ),
            'enumerate' => Outcome::dateList(
                static fn (): array => iterator_to_array($range(), false),
            ),
            default => self::fail("unknown operation {$row['operation']}"),
        };

        Outcome::assertMatches($row['result'], $actual);
    }

    public function testTheDefaultArgumentsMatchTheCSharpOnes(): void
    {
        $range = NepaliDateRange::of(Outcome::bs('2081-04-13'), Outcome::bs('2081-04-21'));

        self::assertSame(
            Outcome::dateList(static fn (): array => $range->workingDays(true)),
            Outcome::dateList(static fn (): array => $range->workingDays(excludeSunday: true)),
        );
        self::assertSame(
            Outcome::dateList(static fn (): array => $range->weekendDays(false)),
            Outcome::dateList(static fn (): array => $range->weekendDays(includeSunday: false)),
        );
        self::assertSame(
            Outcome::dateList(static fn (): array => $range->datesWithInterval()),
            Outcome::dateList(static fn (): array => $range->datesWithInterval(1)),
        );
    }
}
