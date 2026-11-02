<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** csharp-golden/day-diff.tsv (D-07): daysUntil is always other - this, in both directions. */
final class DayDiffTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/day-diff.tsv');
    }

    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        [$ay, $am, $ad] = array_map('intval', explode('-', $row['a']));
        [$by, $bm, $bd] = array_map('intval', explode('-', $row['b']));
        $a = NepaliDate::fromBs($ay, $am, $ad);
        $b = NepaliDate::fromBs($by, $bm, $bd);

        self::assertSame((int) $row['days_until'], $a->daysUntil($b));
    }
}
