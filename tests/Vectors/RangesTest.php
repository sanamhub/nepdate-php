<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\NepaliDateRange;
use NepDate\NepDateException;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/ranges.tsv (NR01, NR02, NR06, NR15-NR21, NR27, NR30): construction, length,
 * containment, relations, the set operations, equality, iteration and the default string form.
 *
 * Rows whose note is D-01 or D-16 hold the port's value, not the C# error: a port has no empty
 * range and never throws merely because a range touches the end of the data.
 */
final class RangesTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/ranges.tsv');
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
            'construct' => Outcome::range($range),
            'length' => Outcome::scalar(static fn (): int => $range()->count()),
            'equals' => Outcome::scalar(
                static fn (): bool => $range()->equals(Outcome::parseRange($argument)),
            ),
            'contains_date' => Outcome::scalar(
                static fn (): bool => $range()->contains(Outcome::bs($argument)),
            ),
            'contains_range' => Outcome::scalar(
                static fn (): bool => $range()->containsRange(Outcome::parseRange($argument)),
            ),
            'overlaps' => Outcome::scalar(
                static fn (): bool => $range()->overlaps(Outcome::parseRange($argument)),
            ),
            'is_adjacent_to' => Outcome::scalar(
                static fn (): bool => $range()->isAdjacentTo(Outcome::parseRange($argument)),
            ),
            'intersect' => Outcome::range(
                static fn (): ?NepaliDateRange => $range()->intersect(Outcome::parseRange($argument)),
            ),
            'union' => Outcome::range(
                static fn (): NepaliDateRange => $range()->union(Outcome::parseRange($argument)),
            ),
            'except' => Outcome::rangeList(
                static fn (): array => $range()->except(Outcome::parseRange($argument)),
            ),
            'to_string' => Outcome::scalar(static fn (): string => (string) $range()),
            'enumerate' => Outcome::dateList(
                static fn (): array => iterator_to_array($range(), false),
            ),
            default => self::fail("unknown operation {$row['operation']}"),
        };

        Outcome::assertMatches($row['result'], $actual);
    }

    public function testEveryOperationOfTheFileIsCovered(): void
    {
        $operations = [];
        foreach (Vectors::load('csharp-golden/ranges.tsv') as $row) {
            $operations[$row['operation']] = true;
        }

        self::assertCount(12, $operations);
    }

    public function testARejectedRangeIsReportedAsOutOfRange(): void
    {
        try {
            NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 20), NepaliDate::fromBs(2081, 4, 10));
            self::fail('a range must not start after it ends');
        } catch (NepDateException $error) {
            self::assertSame(\NepDate\ErrorKind::OutOfRange, $error->kind());
        }
    }
}
