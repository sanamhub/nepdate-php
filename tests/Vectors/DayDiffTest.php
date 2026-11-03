<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/day-diff.tsv (NA04, D-07): `daysUntil` is always `other - this`, in both
 * directions, and `diff()->totalDays` carries the same sign.
 *
 * The file's `operator_minus` column is the C# operator direction, `this - other`. PHP has no
 * operator overloading, so `API.md`'s PHP column for NA05 is "no operator; use `daysUntil`", and
 * that column has nothing to assert beyond its negation, which `daysUntil` already covers.
 */
final class DayDiffTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/day-diff.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        [$ay, $am, $ad] = array_map('intval', explode('-', $row['a']));
        [$by, $bm, $bd] = array_map('intval', explode('-', $row['b']));
        $a = NepaliDate::fromBs($ay, $am, $ad);
        $b = NepaliDate::fromBs($by, $bm, $bd);

        self::assertSame((int) $row['days_until'], $a->daysUntil($b));
        self::assertSame((int) $row['days_until'], $a->diff($b)->totalDays);
        self::assertSame((int) $row['operator_minus'], -$a->daysUntil($b));
    }
}
