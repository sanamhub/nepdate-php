<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\Lang;
use NepDate\NepaliDate;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/dates.tsv: weekday, day-of-year, month length, every string form, and the
 * fiscal-year/quarter boundaries, for 1019 BS dates spanning the whole data range.
 */
final class DatesTest extends TestCase
{
    /** @return list<array<string, string>> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/dates.tsv');
    }

    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        [$y, $m, $d] = array_map('intval', explode('-', $row['bs']));
        $date = NepaliDate::fromBs($y, $m, $d);

        self::assertSame($row['weekday'], $date->weekday()->name);
        self::assertSame((int) $row['day_of_year'], $date->dayOfYear());
        self::assertSame((int) $row['month_length'], $date->monthLength());
        self::assertSame($row['to_string'], (string) $date);
        self::assertSame($row['unicode'], $date->toShortString(lang: Lang::Ne));
        self::assertSame($row['long_en'], $date->toLongString());
        self::assertSame($row['long_en_weekday'], $date->toLongString(weekday: true));
        self::assertSame($row['long_ne'], $date->toLongString(lang: Lang::Ne));
        self::assertSame($row['long_ne_weekday'], $date->toLongString(weekday: true, lang: Lang::Ne));
        self::assertSame($row['dmy_dash_nopad'], $date->format("d-M-yyyy"));

        $fy = $date->fiscalYear();
        if (!Vectors::isError($row['fy_start'])) {
            self::assertSame($row['fy_start'], $fy->start()->isoformat());
        }
        if (!Vectors::isError($row['fy_end'])) {
            self::assertSame($row['fy_end'], $fy->end()->isoformat());
        }
        if (!Vectors::isError($row['quarter_start'])) {
            self::assertSame($row['quarter_start'], $fy->quarterRange($date->quarter())->start()->isoformat());
        }
        if (!Vectors::isError($row['quarter_end'])) {
            self::assertSame($row['quarter_end'], $fy->quarterRange($date->quarter())->end()->isoformat());
        }
    }
}
