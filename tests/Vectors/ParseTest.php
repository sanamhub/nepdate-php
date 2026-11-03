<?php

declare(strict_types=1);

namespace NepDate\Tests\Vectors;

use NepDate\NepaliDate;
use NepDate\Tests\Support\Outcome;
use NepDate\Tests\Support\Vectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * parse.tsv (NP01, NP03, NP07, NP08): the strict parser of PARSING.md §1 and the lenient parser
 * of PARSING.md §2, 69 rows with an `ERR:Kind` cell naming the error kind of section 4.
 *
 * This file is the authoritative one; `csharp-golden/parse.tsv` records what C# does instead.
 */
final class ParseTest extends TestCase
{
    /** @return list<array{array<string, string>}> */
    public static function rows(): array
    {
        return Vectors::provider('parse.tsv');
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('rows')]
    public function testRow(array $row): void
    {
        $input = $row['input'];

        Outcome::assertMatches($row['strict'], Outcome::date(static fn () => NepaliDate::parse($input)));
        Outcome::assertMatches($row['lenient'], Outcome::date(static fn () => NepaliDate::parseLenient($input)));

        self::assertSame(
            Vectors::isError($row['strict']),
            NepaliDate::tryParse($input) === null,
            'tryParse returns null exactly when parse fails',
        );
        self::assertSame(
            Vectors::isError($row['lenient']),
            NepaliDate::tryParseLenient($input) === null,
            'tryParseLenient returns null exactly when parseLenient fails',
        );
    }
}
