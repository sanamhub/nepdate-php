<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDateRange;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/range-splits.tsv (NR22, NR23): `splitByMonth` and `splitByFiscalQuarter`, with
 * the first and last piece clipped to the range ends and, per D-16, no throw at 2199-12-30.
 */
final class RangeSplitsTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/range-splits.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        $start = $row['start'];
        $end = $row['end'];
        $range = static fn (): NepaliDateRange => NepaliDateRange::of(Outcome::bs($start), Outcome::bs($end));

        $actual = match ($row['operation']) {
            'split_by_month' => Outcome::rangeList(static fn (): array => $range()->splitByMonth()),
            'split_by_fiscal_quarter' => Outcome::rangeList(
                static fn (): array => $range()->splitByFiscalQuarter(),
            ),
            default => self::fail("unknown operation {$row['operation']}"),
        };

        Outcome::assertMatches($row['result'], $actual);
    }

    public function testThePiecesAreContiguousAndCoverTheRange(): void
    {
        $range = NepaliDateRange::of(Outcome::bs('2081-01-01'), Outcome::bs('2081-12-31'));

        foreach ([$range->splitByMonth(), $range->splitByFiscalQuarter()] as $pieces) {
            self::assertSame($range->start()->isoformat(), $pieces[0]->start()->isoformat());
            self::assertSame($range->end()->isoformat(), $pieces[count($pieces) - 1]->end()->isoformat());
            $expectedDays = 0;
            foreach ($pieces as $piece) {
                $expectedDays += $piece->count();
            }
            self::assertSame($range->count(), $expectedDays);
            for ($i = 1; $i < count($pieces); $i++) {
                self::assertTrue($pieces[$i - 1]->isAdjacentTo($pieces[$i]));
            }
        }
    }
}
