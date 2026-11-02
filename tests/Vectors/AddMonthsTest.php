<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\MonthOverflow;
use NepDate\NepaliDate;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** csharp-golden/add-months.tsv: NepaliDate::addMonths under both MonthOverflow modes. */
final class AddMonthsTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/add-months.tsv');
    }

    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        [$y, $m, $d] = array_map('intval', explode('-', $row['bs']));
        $date = NepaliDate::fromBs($y, $m, $d);
        $months = (int) $row['months'];

        $this->checkOne($date, $months, MonthOverflow::Clamp, $row['clamp']);
        $this->checkOne($date, $months, MonthOverflow::Spill, $row['overflow']);
    }

    private function checkOne(NepaliDate $date, int $months, MonthOverflow $mode, string $expected): void
    {
        if (Vectors::isError($expected)) {
            try {
                $date->addMonths($months, $mode);
                self::fail("expected addMonths({$months}, {$mode->name}) to throw");
            } catch (\NepDate\NepDateException) {
                self::assertTrue(true);
            }

            return;
        }

        [$ry, $rm, $rd] = array_map('intval', explode('-', $expected));
        $result = $date->addMonths($months, $mode);
        self::assertTrue(NepaliDate::fromBs($ry, $rm, $rd)->equals($result), "{$result} !== {$expected}");
    }
}
