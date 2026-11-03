<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\MonthOverflow;
use NepDate\NepaliDate;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** csharp-golden/add-months.tsv (NA01, NA03): `addMonths` under both MonthOverflow modes. */
final class AddMonthsTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/add-months.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        [$y, $m, $d] = array_map('intval', explode('-', $row['bs']));
        $date = NepaliDate::fromBs($y, $m, $d);
        $months = (int) $row['months'];

        Outcome::assertMatches(
            $row['clamp'],
            Outcome::date(static fn (): NepaliDate => $date->addMonths($months, MonthOverflow::Clamp)),
        );
        Outcome::assertMatches(
            $row['overflow'],
            Outcome::date(static fn (): NepaliDate => $date->addMonths($months, MonthOverflow::Spill)),
        );
    }

    /**
     * The rows whose month count is a whole number of years.
     *
     * @return list<array{array<string, string>}>
     */
    public static function yearRows(): array
    {
        return array_values(array_filter(
            Vectors::provider('csharp-golden/add-months.tsv'),
            static fn (array $case): bool => (int) $case[0]['months'] % 12 === 0,
        ));
    }

    /**
     * `addYears` is `addMonths(years * 12)` with the same mode (NA03), so every row whose month
     * count is a whole number of years must also hold for the year form.
     *
     * @param array<string, string> $row
     */
    #[DataProvider('yearRows')]
    public function testYearRowsAgreeWithTheMonthRows(array $row): void
    {
        $months = (int) $row['months'];
        [$y, $m, $d] = array_map('intval', explode('-', $row['bs']));
        $date = NepaliDate::fromBs($y, $m, $d);

        Outcome::assertMatches(
            $row['clamp'],
            Outcome::date(static fn (): NepaliDate => $date->addYears(intdiv($months, 12), MonthOverflow::Clamp)),
        );
        Outcome::assertMatches(
            $row['overflow'],
            Outcome::date(static fn (): NepaliDate => $date->addYears(intdiv($months, 12), MonthOverflow::Spill)),
        );
    }
}
