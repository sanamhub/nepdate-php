<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\DateOrder;
use NepDate\NepaliDateRange;
use NepDate\Separator;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/range-format.tsv (NR28, NR29): the default `start - end` form and the short form
 * in every order, separator and language (D-15 adds the Nepali rows).
 */
final class RangeFormatTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/range-format.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        $start = $row['start'];
        $end = $row['end'];
        $range = static fn (): NepaliDateRange => NepaliDateRange::of(Outcome::bs($start), Outcome::bs($end));

        if ($row['kind'] === 'default') {
            Outcome::assertMatches($row['result'], Outcome::scalar(static fn (): string => (string) $range()));

            return;
        }

        Outcome::assertMatches(
            $row['result'],
            Outcome::scalar(static fn (): string => $range()->toStringWith(
                self::order($row['order']),
                self::separator($row['separator']),
                Outcome::lang($row['lang']),
            )),
        );
    }

    public function testTheShortFormDefaultsMatchTheCSharpOrderAndSeparator(): void
    {
        $range = NepaliDateRange::of(Outcome::bs('2081-04-10'), Outcome::bs('2081-04-20'));

        self::assertSame('2081/04/10 - 2081/04/20', $range->toStringWith(DateOrder::Ymd, Separator::Slash));
        self::assertSame('2081/04/10 - 2081/04/20', (string) $range);
    }

    private static function order(string $name): DateOrder
    {
        return match ($name) {
            'Ymd' => DateOrder::Ymd,
            'Ydm' => DateOrder::Ydm,
            'Myd' => DateOrder::Myd,
            'Mdy' => DateOrder::Mdy,
            'Dym' => DateOrder::Dym,
            'Dmy' => DateOrder::Dmy,
            default => self::fail("unknown order {$name}"),
        };
    }

    private static function separator(string $name): Separator
    {
        return match ($name) {
            'Slash' => Separator::Slash,
            'Backslash' => Separator::Backslash,
            'Dot' => Separator::Dot,
            'Underscore' => Separator::Underscore,
            'Dash' => Separator::Dash,
            'Space' => Separator::Space,
            default => self::fail("unknown separator {$name}"),
        };
    }
}
