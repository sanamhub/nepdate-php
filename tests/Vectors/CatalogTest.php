<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\TestCase;

/**
 * csharp-tests/catalog.tsv: every test case of C# NepDate's own suite, tagged with the phase of the
 * port that covers it (`spec/README.md`, `PORT-PLAN.md`).
 *
 * The catalog is an inventory, not a set of expected outputs: its `asserts` column holds the C#
 * assertion source, so there is nothing to compare against. The behaviour it describes is asserted
 * by the golden vectors this suite reads elsewhere: `dates.tsv` and `add-*.tsv` for the calendar,
 * `format-*.tsv` for the formatting, `parse.tsv` for both parsers, the range files for the ranges,
 * and `tests/Unit` for the members no vector can reach (the clock methods, the try* forms, the enum
 * tables).
 *
 * What this test adds is that the inventory cannot drift silently: every row has the columns it
 * claims, every phase is one this port knows, and a new phase or a lost row fails here.
 */
final class CatalogTest extends TestCase
{
    /** The phases the port knows: 1 to 4, D-14 for the row that decision removes, n/a for no port. */
    private const array PHASES = ['1', '2', '3', '4', 'D-14', 'n/a'];

    public function testEveryRowIsWellFormedAndCoveredByAKnownPhase(): void
    {
        $rows = Vectors::load('csharp-tests/catalog.tsv');
        self::assertCount(542, $rows);

        $seen = [];
        $phases = [];
        foreach ($rows as $row) {
            $id = $row['id'];
            self::assertNotSame('', $id);
            self::assertFalse(isset($seen[$id]), "duplicate catalog row: {$id}");
            $seen[$id] = true;

            self::assertNotSame('', $row['file'], $id);
            self::assertNotSame('', $row['method'], $id);
            // A demo row carries its assertions in the C# file, not in the column.
            self::assertArrayHasKey('asserts', $row, $id);
            self::assertContains($row['phase'], self::PHASES, "unknown phase in {$id}");

            $phases[$row['phase']] = ($phases[$row['phase']] ?? 0) + 1;
        }

        $expected = ['1' => 113, '2' => 180, '3' => 148, '4' => 40, 'D-14' => 17, 'n/a' => 44];
        ksort($expected);
        ksort($phases);

        self::assertSame($expected, $phases);
    }

    public function testTheCatalogNamesTheFilesOfTheCsharpSuite(): void
    {
        $files = [];
        foreach (Vectors::load('csharp-tests/catalog.tsv') as $row) {
            $files[$row['file']] = true;
        }

        self::assertGreaterThan(10, count($files));
        foreach (array_keys($files) as $file) {
            self::assertStringEndsWith('.cs', $file);
        }
    }
}
