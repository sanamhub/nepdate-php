<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/serialization.tsv (NC10, D-11): the native JSON hook. The `json` column is
 * literal JSON text, quotes included, so the loader reads it as plain tab-separated text.
 *
 * D-01 removes the C# zero-value default, so a JSON `null` is an error rather than 0000-00-00.
 */
final class SerializationTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/serialization.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        $input = $row['input'];

        switch ($row['kind']) {
            case 'write':
                self::assertSame($row['json'], self::encode(NepaliDate::parse($input)));

                return;
            case 'read':
                Outcome::assertMatches(
                    $row['result'],
                    Outcome::date(static fn (): NepaliDate => NepaliDate::fromJson($input)),
                );

                return;
            case 'roundtrip':
                $written = self::encode(NepaliDate::parse($input));
                self::assertSame($row['json'], $written);
                Outcome::assertMatches(
                    $row['result'],
                    Outcome::date(static fn (): NepaliDate => NepaliDate::fromJson($written)),
                );

                return;
            default:
                self::fail("unknown kind {$row['kind']}");
        }
    }

    public function testTheWrittenFormIsASingleQuotedString(): void
    {
        $date = NepaliDate::parse('2081-04-15');

        self::assertSame('"2081-04-15"', self::encode($date));
        self::assertSame('{"d":"2081-04-15"}', json_encode(['d' => $date], JSON_THROW_ON_ERROR));
    }

    private static function encode(NepaliDate $date): string
    {
        return json_encode($date, JSON_THROW_ON_ERROR);
    }
}
