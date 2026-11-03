<?php

declare(strict_types=1);

/**
 * The competitor measurements of `shared/spec/BENCHMARK.md` §7.
 *
 * Each competitor is installed in `bench/competitors/` from that directory's `composer.json`, never
 * in the root lock, so the library's own dependency tree stays clean:
 *
 *   composer install --working-dir=bench/competitors
 *
 * A competitor that is not installed is skipped, and the markdown says so rather than showing a
 * number that was not measured. A competitor that rejects part of the 64-date spread is measured
 * over the dates it accepts, and the row says how many; its numbers are not ranked against a
 * library that answers all 64.
 *
 * The `agrees` column compares the competitor's conversion with the shared data: a fast library
 * with wrong dates is listed, never ranked first (BENCHMARK.md §4).
 */

require_once __DIR__ . '/Harness.php';
require_once __DIR__ . '/framework-stubs.php';

use NepDate\Bench\Harness;
use NepDate\NepaliDate;

$autoload = __DIR__ . '/competitors/vendor/autoload.php';
if (!is_file($autoload)) {
    return [];
}

require_once $autoload;

$dates = Harness::dates();

/**
 * @param callable(NepaliDate): (string|null) $convert BS to AD as `Y-m-d`
 * @param callable(NepaliDate): (string|null) $back AD to BS as `Y-m-d`
 * @return array{results: list<array{label: string, ns: float, agrees: string, accepted: int}>}
 */
$measure = static function (callable $convert, callable $back) use ($dates): array {
    $accepted = [];
    $right = 0;
    foreach ($dates as $date) {
        try {
            $converted = $convert($date);
            $again = $back($date);
        } catch (Throwable) {
            continue;
        }
        if ($converted === null || $again === null) {
            continue;
        }
        $accepted[] = $date;
        $expectedAd = $date->toDateTime()->format('Y-m-d');
        $expectedBs = sprintf('%04d-%02d-%02d', $date->year(), $date->month(), $date->day());
        if ($converted === $expectedAd && $again === $expectedBs) {
            $right++;
        }
    }

    $total = count($dates);
    $agrees = $accepted === []
        ? 'no input accepted'
        : sprintf('%d of %d (%d accepted)', $right, $total, count($accepted));

    if ($accepted === []) {
        return ['results' => [['label' => 'BS to AD', 'ns' => 0.0, 'agrees' => $agrees, 'accepted' => 0]]];
    }

    $forward = Harness::measure(
        static fn (mixed $date): ?string => $date instanceof NepaliDate ? $convert($date) : null,
        $accepted,
    );
    $reverse = Harness::measure(
        static fn (mixed $date): ?string => $date instanceof NepaliDate ? $back($date) : null,
        $accepted,
    );

    return [
        'results' => [
            ['label' => 'BS to AD', 'ns' => $forward['mean'], 'agrees' => $agrees, 'accepted' => count($accepted)],
            ['label' => 'AD to BS (text)', 'ns' => $reverse['mean'], 'agrees' => $agrees, 'accepted' => count($accepted)],
        ],
    ];
};

$competitors = [];

/**
 * @param class-string $class
 * @param callable(NepaliDate): (string|null) $convert
 * @param callable(NepaliDate): (string|null) $back
 */
$register = static function (string $name, string $class, callable $convert, callable $back) use (&$competitors, $measure): void {
    if (!class_exists($class)) {
        return;
    }
    $competitors[$name] = $measure($convert, $back);
};

$register(
    'anuzpandey/laravel-nepali-date 3.3.0',
    'Anuzpandey\\LaravelNepaliDate\\LaravelNepaliDate',
    static function (NepaliDate $date): ?string {
        $text = Anuzpandey\LaravelNepaliDate\LaravelNepaliDate::from(sprintf(
            '%04d-%02d-%02d',
            $date->year(),
            $date->month(),
            $date->day(),
        ))->toEnglishDate('Y-m-d', 'en');

        return is_string($text) ? $text : null;
    },
    static function (NepaliDate $date): ?string {
        $text = Anuzpandey\LaravelNepaliDate\LaravelNepaliDate::from(
            $date->toDateTime()->format('Y-m-d'),
        )->toNepaliDate('Y-m-d', 'en');

        return is_string($text) ? $text : null;
    },
);

$register(
    'ernilambar/nepali-date 1.0.9',
    'Nilambar\\NepaliDate\\NepaliDate',
    static function (NepaliDate $date): ?string {
        $converted = (new Nilambar\NepaliDate\NepaliDate())->convertBsToAd(
            $date->year(),
            $date->month(),
            $date->day(),
        );

        return is_array($converted) && isset($converted['year'], $converted['month'], $converted['day'])
            ? sprintf('%04d-%02d-%02d', (int) $converted['year'], (int) $converted['month'], (int) $converted['day'])
            : null;
    },
    static function (NepaliDate $date): ?string {
        $gregorian = $date->toDateTime();
        $converted = (new Nilambar\NepaliDate\NepaliDate())->convertAdToBs(
            (int) $gregorian->format('Y'),
            (int) $gregorian->format('n'),
            (int) $gregorian->format('j'),
        );

        return is_array($converted) && isset($converted['year'], $converted['month'], $converted['day'])
            ? sprintf('%04d-%02d-%02d', (int) $converted['year'], (int) $converted['month'], (int) $converted['day'])
            : null;
    },
);

return $competitors;
