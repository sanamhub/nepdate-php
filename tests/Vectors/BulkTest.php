<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use DateTimeImmutable;
use NepDate\NepaliDate;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/bulk.tsv (NC04-NC08): the ordered batch conversions. The C# `useParallel` and
 * `batchSize` arguments do not cross into the port API, so both the plain and the batch entry
 * point of C# map onto the same port function.
 */
final class BulkTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/bulk.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        $input = explode(';', $row['input']);

        $actual = match ($row['operation']) {
            'to_nepali_dates', 'batch_to_nepali_dates' => Outcome::dateList(
                static fn (): array => NepaliDate::fromGregorianBatch(self::gregorian($input)),
            ),
            'to_english_dates_from_strings' => self::english(
                static fn (): array => NepaliDate::parseBatch($input),
            ),
            'to_english_dates_from_dates' => self::english(
                static fn (): array => NepaliDate::toGregorianBatch(self::nepali($input)),
            ),
            'batch_to_english_dates' => self::english(
                static fn (): array => NepaliDate::toGregorianBatch(self::nepali($input)),
            ),
            default => self::fail("unknown operation {$row['operation']}"),
        };

        Outcome::assertMatches($row['output'], $actual);
    }

    public function testTheResultKeepsInputOrder(): void
    {
        $dates = self::gregorian(['2024-07-31', '1844-04-11', '2143-04-15']);
        $converted = NepaliDate::fromGregorianBatch($dates);

        self::assertSame(['2081-04-16', '1901-01-01', '2199-12-30'], array_map(
            static fn (NepaliDate $date): string => $date->isoformat(),
            $converted,
        ));
        self::assertSame(
            ['2024-07-31', '1844-04-11', '2143-04-15'],
            array_map(
                static fn (DateTimeImmutable $date): string => $date->format('Y-m-d'),
                NepaliDate::toGregorianBatch($converted),
            ),
        );
    }

    /**
     * @param list<string> $texts
     * @return list<DateTimeImmutable>
     */
    private static function gregorian(array $texts): array
    {
        return array_map(static fn (string $text): DateTimeImmutable => new DateTimeImmutable($text), $texts);
    }

    /**
     * @param list<string> $texts
     * @return list<NepaliDate>
     */
    private static function nepali(array $texts): array
    {
        return array_map(static fn (string $text): NepaliDate => NepaliDate::parse($text), $texts);
    }

    /**
     * @param callable(): list<DateTimeImmutable> $call
     */
    private static function english(callable $call): string
    {
        return Outcome::scalar(static function () use ($call): string {
            $parts = array_map(
                static fn (DateTimeImmutable $date): string => $date->format('Y-m-d'),
                $call(),
            );

            return implode(';', $parts);
        });
    }
}
