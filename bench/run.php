<?php

declare(strict_types=1);

/**
 * The benchmark of `shared/spec/BENCHMARK.md`: B1 to B8 over the 64 shared inputs, plus the
 * competitor rows that `docs/competitors.md` selected.
 *
 *   php bench/run.php                     markdown table on stdout
 *   php bench/run.php --json=out.json     also write the raw figures
 *   php bench/run.php --iterations=20     more measured iterations
 *
 * Every operation's result is consumed: B1 and B2 compare the result with the table, B3 to B8
 * return it from the closure, so nothing can be optimised away. Bytes per operation are reported
 * as "not measured": PHP's allocator reuses freed memory, so a per-operation figure would be an
 * artefact rather than a measurement (BENCHMARK.md §4 allows the omission, as for Python).
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Harness.php';

use NepDate\Bench\Harness;
use NepDate\Lang;
use NepDate\MonthOverflow;
use NepDate\NepaliDate;

use function NepDate\Patro\info;

$args = [];
foreach (array_slice($_SERVER['argv'] ?? [], 1) as $argument) {
    if (is_string($argument) && str_starts_with($argument, '--')) {
        [$key, $value] = array_pad(explode('=', $argument, 2), 2, '1');
        $args[substr($key, 2)] = $value;
    }
}

$iterations = max(3, (int) ($args['iterations'] ?? 10));
$dates = Harness::dates();
$texts = Harness::texts();
$gregorian = Harness::gregorianDates();
$patroDates = Harness::patroDates();

$operations = [];

$operations['B1'] = [
    'label' => 'BS to AD (fromBs, then toDateTime)',
    'unit' => 'core',
    'run' => static fn (mixed $date): string => $date instanceof NepaliDate
        ? $date->toDateTime()->format('Y-m-d')
        : '',
    'inputs' => $dates,
];

$operations['B2'] = [
    'label' => 'AD to BS (fromGregorian)',
    'unit' => 'core',
    'run' => static fn (mixed $date): int => $date instanceof DateTimeInterface
        ? NepaliDate::fromGregorian($date)->serial()
        : 0,
    'inputs' => $gregorian,
];

$operations['B3'] = [
    'label' => 'add 1000 days',
    'unit' => 'core',
    'run' => static function (mixed $date): string {
        return $date instanceof NepaliDate ? $date->addDays(1000)->isoformat() : '';
    },
    'inputs' => $dates,
];

$operations['B4'] = [
    'label' => 'add 13 months, Clamp',
    'unit' => 'core',
    'run' => static function (mixed $date): string {
        return $date instanceof NepaliDate ? $date->addMonths(13, MonthOverflow::Clamp)->isoformat() : '';
    },
    'inputs' => $dates,
];

$operations['B5'] = [
    'label' => 'long format, Nepali, with weekday',
    'unit' => 'core',
    'run' => static function (mixed $date): string {
        return $date instanceof NepaliDate ? $date->toLongString(weekday: true, lang: Lang::Ne) : '';
    },
    'inputs' => $dates,
];

$operations['B6'] = [
    'label' => 'strict parse of YYYY/MM/DD',
    'unit' => 'core',
    'run' => static function (mixed $text): int {
        return is_string($text) ? NepaliDate::parse($text)->serial() : 0;
    },
    'inputs' => $texts,
];

$operations['B7'] = [
    'label' => 'patro info (tithi, holiday, events)',
    'unit' => 'patro',
    'run' => static function (mixed $date): string {
        if (!$date instanceof NepaliDate) {
            return '';
        }
        $entry = info($date);
        $events = '';
        foreach ($entry->events() as $event) {
            $events .= $event->en;
        }

        return ($entry->tithi()?->en ?? '') . ($entry->isPublicHoliday() ? '1' : '0') . $events;
    },
    'inputs' => $patroDates,
];

$operations['B8'] = [
    'label' => 'patro month grid (every day of the month)',
    'unit' => 'patro',
    'run' => static function (mixed $date): int {
        if (!$date instanceof NepaliDate) {
            return 0;
        }
        $range = NepDate\NepaliDateRange::forMonth($date->year(), $date->month());
        $seen = 0;
        foreach ($range as $day) {
            $entry = info($day);
            if ($entry->tithi() !== null || $entry->isPublicHoliday()) {
                $seen++;
            }
        }

        return $seen;
    },
    'inputs' => $patroDates,
];

$results = [];
foreach ($operations as $id => $operation) {
    $results[$id] = [
        'label' => $operation['label'],
        'unit' => $operation['unit'],
        'measurement' => Harness::measure($operation['run'], $operation['inputs'], $iterations),
    ];
}

$competitors = require __DIR__ . '/competitors.php';

$rows = [];
foreach ($results as $id => $result) {
    $rows[] = [
        'id' => $id,
        'label' => $result['label'],
        'unit' => $result['unit'],
        'ns' => $result['measurement']['mean'],
        'sd' => $result['measurement']['stddev'],
    ];
}

$payload = [
    'php' => PHP_VERSION,
    'iterations' => $iterations,
    'inputs' => count($dates),
    'results' => $results,
    'competitors' => $competitors,
];

if (isset($args['json'])) {
    file_put_contents($args['json'], json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
}

$format = static function (float $nanoseconds): string {
    return $nanoseconds >= 1000
        ? sprintf('%.0f', $nanoseconds)
        : sprintf('%.1f', $nanoseconds);
};

echo "| ID | Operation | Unit | ns/op | sd | bytes/op |\n";
echo "|---|---|---|---:|---:|---|\n";
foreach ($rows as $row) {
    printf(
        "| %s | %s | %s | %s | %s | not measured |\n",
        $row['id'],
        $row['label'],
        $row['unit'],
        $format($row['ns']),
        $format($row['sd']),
    );
}

echo "\n";
echo 'PHP ' . PHP_VERSION . ", {$iterations} measured iterations over " . count($dates) . " inputs.\n";

if ($competitors !== []) {
    echo "\n| Competitor | Operation | ns/op | inputs it answers | agrees with the shared data |\n";
    echo "|---|---|---:|---:|---|\n";
    foreach ($competitors as $name => $competitor) {
        foreach ($competitor['results'] as $entry) {
            printf(
                "| %s | %s | %s | %d | %s |\n",
                $name,
                $entry['label'],
                $entry['ns'] === 0.0 ? 'not measured' : $format($entry['ns']),
                $entry['accepted'],
                $entry['agrees'],
            );
        }
    }
}
