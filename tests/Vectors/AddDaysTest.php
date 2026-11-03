<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** csharp-golden/add-days.tsv (NA02): `addDays`, including the out-of-range rows. */
final class AddDaysTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/add-days.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        [$y, $m, $d] = array_map('intval', explode('-', $row['bs']));
        $date = NepaliDate::fromBs($y, $m, $d);
        $days = (int) $row['days'];

        Outcome::assertMatches(
            $row['result'],
            Outcome::date(static fn (): NepaliDate => $date->addDays($days)),
        );
    }
}
