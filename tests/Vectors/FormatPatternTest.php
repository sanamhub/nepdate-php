<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/format-pattern.tsv (NF06): the custom pattern language, 452 rows across the
 * whole data range.
 *
 * D-04 makes two rows deliberate deviations: C# writes `Ash` for both Ashad (3) and Ashoj (6),
 * the port writes `Asa` and `Aso`. Those rows are skipped here and asserted separately.
 */
final class FormatPatternTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/format-pattern.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        $date = Outcome::bs($row['bs']);
        if (self::isAshadOrAshojShortMonth($row['pattern'], $date)) {
            self::markTestSkipped('D-04: the port writes Asa and Aso where C# writes Ash twice');
        }

        self::assertSame($row['result'], $date->format($row['pattern']), $row['pattern']);
    }

    public function testTheTwoAbbreviationsFollowD04(): void
    {
        self::assertSame('Asa', Outcome::bs('2081-03-01')->format('MMM'));
        self::assertSame('Aso', Outcome::bs('2081-06-01')->format('MMM'));
        self::assertSame('Ashad', Outcome::bs('2081-03-01')->format('MMMM'));
        self::assertSame('Ashoj', Outcome::bs('2081-06-01')->format('MMMM'));
    }

    private static function isAshadOrAshojShortMonth(string $pattern, NepaliDate $date): bool
    {
        if (!str_contains($pattern, 'MMM') || str_contains($pattern, 'MMMM')) {
            return false;
        }

        return $date->month() === 3 || $date->month() === 6;
    }
}
