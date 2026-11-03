<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\TestCase;

/**
 * year-length.tsv (ND20, D-12): the length of every BS year, 364 to 367 days, summed from the
 * twelve month lengths. C# has no year-length API, so this reference file is derived from the
 * data rather than recorded from C#.
 */
final class YearLengthTest extends TestCase
{
    public function testEveryYearLength(): void
    {
        $rows = Vectors::load('year-length.tsv');
        self::assertCount(299, $rows);

        foreach ($rows as $row) {
            $year = (int) $row['bs_year'];
            $expected = (int) $row['year_length'];
            self::assertSame($expected, NepaliDate::fromBs($year, 1, 1)->yearLength(), "BS {$year}");
            self::assertSame($expected, NepaliDate::fromBs($year, 12, 1)->yearLength(), "BS {$year}");
        }
    }

    public function testTheYearLengthIsTheSumOfTheTwelveMonthLengths(): void
    {
        foreach (Vectors::load('year-length.tsv') as $row) {
            $year = (int) $row['bs_year'];
            $sum = 0;
            for ($month = 1; $month <= 12; $month++) {
                $sum += NepaliDate::fromBs($year, $month, 1)->monthLength();
            }
            self::assertSame($sum, NepaliDate::fromBs($year, 12, 1)->yearLength(), "BS {$year}");
        }
    }

    public function testTheLengthsStayWithinTheDataRange(): void
    {
        $seen = [];
        foreach (Vectors::load('year-length.tsv') as $row) {
            $seen[(int) $row['year_length']] = true;
        }
        ksort($seen);

        self::assertSame([364, 365, 366, 367], array_keys($seen));
    }
}
