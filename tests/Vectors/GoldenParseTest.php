<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * csharp-golden/parse.tsv: what C# records for the same inputs, kept for comparison only.
 *
 * The `smart` column is not asserted: it is the C# `SmartDateParser` heuristic overload, which
 * D-05 and D-06 replace with the deterministic lenient parser of `parse.tsv`. The strict column
 * must still agree, since the strict grammar is unchanged.
 */
final class GoldenParseTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('csharp-golden/parse.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testStrictColumn(array $row): void
    {
        $input = $row['input'];
        $actual = Outcome::date(static fn () => NepaliDate::parse($input));

        // D-05: the port accepts Devanagari digits in the strict parser and C# does not, so the
        // only rows allowed to disagree are ones where the port accepts a digit the C# run did not.
        if (Vectors::isError($row['strict']) && !Vectors::isError($actual)) {
            self::assertSame(1, preg_match('/[\x{0966}-\x{096F}]/u', $input), 'D-05');

            return;
        }

        Outcome::assertMatches($row['strict'], $actual);
    }

    public function testSmartColumnIsNotPorted(): void
    {
        // D-05 and D-06: the three autoAdjust heuristics and the fuzzy smart parser are gone, so
        // at least one recorded `smart` result cannot be reproduced by the lenient parser.
        $diverged = 0;
        foreach (Vectors::load('csharp-golden/parse.tsv') as $row) {
            if (Vectors::isError($row['smart'])) {
                continue;
            }
            $lenient = Outcome::date(static fn () => NepaliDate::parseLenient($row['input']));
            if ($lenient !== $row['smart']) {
                $diverged++;
            }
        }

        self::assertGreaterThan(0, $diverged, 'the smart column is expected to differ somewhere');
    }
}
