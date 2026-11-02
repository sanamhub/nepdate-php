<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** csharp-golden/add-days.tsv: NepaliDate::addDays, including out-of-range ERR rows. */
final class AddDaysTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/add-days.tsv');
    }

    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        [$y, $m, $d] = array_map('intval', explode('-', $row['bs']));
        $date = NepaliDate::fromBs($y, $m, $d);
        $days = (int) $row['days'];

        if (Vectors::isError($row['result'])) {
            $this->expectException(\NepDate\NepDateException::class);
            $date->addDays($days);

            return;
        }

        [$ry, $rm, $rd] = array_map('intval', explode('-', $row['result']));
        $expected = NepaliDate::fromBs($ry, $rm, $rd);
        self::assertTrue($expected->equals($date->addDays($days)));
    }
}
