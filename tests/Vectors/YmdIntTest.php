<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** csharp-golden/ymd-int.tsv (NC09, D-11): the packed `YYYYMMDD` integer, both directions. */
final class YmdIntTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/ymd-int.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        if ($row['kind'] === 'date') {
            $date = NepaliDate::parse($row['input']);
            self::assertSame((int) $row['ymd'], $date->toYmd());
            self::assertSame(
                $row['result'],
                Outcome::date(static fn (): NepaliDate => NepaliDate::fromYmd((int) $row['ymd'])),
            );

            return;
        }

        $ymd = (int) $row['ymd'];
        Outcome::assertMatches(
            $row['result'],
            Outcome::date(static fn (): NepaliDate => NepaliDate::fromYmd($ymd)),
        );
    }

    public function testTheIntegerRoundTripsForEveryFirstOfTheMonth(): void
    {
        for ($year = 1901; $year <= 2199; $year += 7) {
            for ($month = 1; $month <= 12; $month++) {
                $date = NepaliDate::fromBs($year, $month, 1);
                self::assertSame($year * 10000 + $month * 100 + 1, $date->toYmd());
                self::assertTrue($date->equals(NepaliDate::fromYmd($date->toYmd())));
            }
        }
    }
}
