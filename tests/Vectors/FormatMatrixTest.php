<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\DateOrder;
use NepDate\Separator;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/format-matrix.tsv (NF02, NF08, NF09): every date order crossed with every
 * separator, with and without padding, in both languages, plus the short-buffer rows of
 * `ISpanFormattable.TryFormat`.
 *
 * NF07 is "none; string return" in the PHP column of `API.md`: a PHP string is never too short,
 * so there is no `TryFormat` to port. The rows still pin the capacity rule, which is asserted
 * against the length of the formatted text.
 */
final class FormatMatrixTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/format-matrix.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        $date = Outcome::bs($row['bs']);

        if ($row['kind'] === 'try_format') {
            $text = $row['format'] === 'default' ? (string) $date : $date->format($row['format']);
            $capacity = (int) $row['capacity'];
            if ($row['result'] === 'ERR') {
                self::assertGreaterThan($capacity, strlen($text), 'the buffer would overflow');

                return;
            }
            self::assertSame($row['result'], $text);
            self::assertLessThanOrEqual($capacity, strlen($text), 'the text must fit the buffer');

            return;
        }

        self::assertSame(
            $row['result'],
            $date->toShortString(
                self::order($row['order']),
                self::separator($row['separator']),
                $row['pad'] === 'true',
                Outcome::lang($row['lang']),
            ),
        );
    }

    public function testEveryOrderAndSeparatorIsCovered(): void
    {
        $orders = [];
        $separators = [];
        foreach (Vectors::load('csharp-golden/format-matrix.tsv') as $row) {
            if ($row['kind'] !== 'short') {
                continue;
            }
            $orders[$row['order']] = true;
            $separators[$row['separator']] = true;
        }

        self::assertCount(6, $orders);
        self::assertCount(6, $separators);
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
