<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use DateTimeZone;
use NepDate\FiscalYear;
use NepDate\NepaliDate;
use NepDate\NepaliDateRange;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/range-factories.tsv (NR07-NR11, NR02): `singleDay`, `fromDayCount`, `forMonth`,
 * `forYear`, the fiscal-year range and the constructor, including the refused arguments.
 */
final class RangeFactoriesTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/range-factories.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        $argument = $row['argument'];
        $parts = explode(',', $argument);

        $actual = match ($row['factory']) {
            'single_day' => Outcome::range(
                static fn (): NepaliDateRange => NepaliDateRange::singleDay(Outcome::bs($argument)),
            ),
            'from_day_count' => Outcome::range(
                static fn (): NepaliDateRange => NepaliDateRange::fromDayCount(
                    Outcome::bs($parts[0]),
                    (int) ($parts[1] ?? 0),
                ),
            ),
            'for_month' => Outcome::range(
                static fn (): NepaliDateRange => NepaliDateRange::forMonth(
                    (int) $parts[0],
                    (int) ($parts[1] ?? 0),
                ),
            ),
            'for_calendar_year' => Outcome::range(
                static fn (): NepaliDateRange => NepaliDateRange::forYear((int) $argument),
            ),
            'for_fiscal_year' => Outcome::range(
                static fn (): NepaliDateRange => (new FiscalYear((int) $argument))->range(),
            ),
            'construct' => Outcome::range(
                static fn (): NepaliDateRange => NepaliDateRange::of(
                    Outcome::bs($parts[0]),
                    Outcome::bs($parts[1] ?? ''),
                ),
            ),
            default => self::fail("unknown factory {$row['factory']}"),
        };

        Outcome::assertMatches($row['result'], $actual);
    }

    public function testEveryFactoryOfTheFileIsCovered(): void
    {
        $factories = [];
        foreach (Vectors::load('csharp-golden/range-factories.tsv') as $row) {
            $factories[$row['factory']] = true;
        }

        self::assertCount(6, $factories);
    }

    public function testTheCurrentFactoriesUseTheGivenZone(): void
    {
        $zone = new DateTimeZone('Asia/Kathmandu');
        $today = NepaliDate::today($zone);

        self::assertTrue(NepaliDateRange::currentMonth($zone)->contains($today));
        self::assertTrue(NepaliDateRange::currentCalendarYear($zone)->contains($today));
        self::assertTrue(NepaliDateRange::currentFiscalYear($zone)->contains($today));
        self::assertSame(
            NepaliDateRange::forMonth($today->year(), $today->month())->start()->isoformat(),
            NepaliDateRange::currentMonth($zone)->start()->isoformat(),
        );
    }
}
