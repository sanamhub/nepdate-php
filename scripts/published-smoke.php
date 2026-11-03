<?php

declare(strict_types=1);

/**
 * The install check `verify-published.yml` runs against the packages Packagist serves, from a
 * throw-away consumer project's vendor directory rather than from this checkout.
 *
 *   php scripts/published-smoke.php /tmp/consumer/vendor/autoload.php
 *
 * It fails loudly: a missing class, a wrong value or a missing patro function is a broken release.
 */

use NepDate\Lang;
use NepDate\NepaliDate;
use NepDate\NepaliDateRange;

require __DIR__ . '/lib/cli.php';

$autoload = cliArgs()[1] ?? '';
if ($autoload === '' || !is_file($autoload)) {
    fwrite(STDERR, "usage: php scripts/published-smoke.php <vendor/autoload.php>\n");
    exit(1);
}

require $autoload;

$failures = [];
$check = static function (string $what, mixed $actual, mixed $expected) use (&$failures): void {
    if ($actual !== $expected) {
        $failures[] = sprintf(
            '%s: got %s, expected %s',
            $what,
            var_export($actual, true),
            var_export($expected, true),
        );
    }
};

$date = NepaliDate::fromBs(2081, 4, 15);
$check('NepaliDate::fromBs()->isoformat()', $date->isoformat(), '2081-04-15');
$check('NepaliDate::parse()', NepaliDate::parse('2081/04/15')->isoformat(), '2081-04-15');
$check('NepaliDate::parseLenient()', NepaliDate::parseLenient('15 Shrawan 2081')->isoformat(), '2081-04-15');
$check('__toString()', (string) $date, '2081/04/15');
$check('toLongString()', $date->toLongString(), 'Shrawan 15, 2081');
$check('toLongString(Ne)', $date->toLongString(lang: Lang::Ne), 'साउन १५, २०८१');
$check('format()', $date->format('yyyy-MM-dd'), '2081-04-15');
$check('addDays()', $date->addDays(1)->isoformat(), '2081-04-16');
$check('addMonths()', NepaliDate::fromBs(2081, 4, 32)->addMonths(2)->isoformat(), '2081-06-30');
$check('daysUntil()', NepaliDate::fromBs(2080, 1, 1)->daysUntil($date), 473);
$check('fiscalYear()', $date->fiscalYear()->label(), '2081/82');
$check('json', json_encode($date, JSON_THROW_ON_ERROR), '"2081-04-15"');
$check('fromJson()', NepaliDate::fromJson('"2081-04-15"')->isoformat(), '2081-04-15');
$check('toYmd()', $date->toYmd(), 20810415);
$check('fromGregorianBatch()', NepaliDate::fromGregorianBatch([
    new DateTimeImmutable('2024-07-30'),
])[0]->isoformat(), '2081-04-15');

$range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));
$check('NepaliDateRange::count()', $range->count(), 11);
$check('NepaliDateRange::splitByMonth()', count($range->splitByMonth()), 1);
$check('NepaliDateRange::toStringWith()', $range->toStringWith(), '2081/04/10 - 2081/04/20');

if (!function_exists('NepDate\\Patro\\info')) {
    $failures[] = 'NepDate\\Patro\\info() is missing: the patro package did not load';
} else {
    $info = NepDate\Patro\info(NepaliDate::fromBs(2081, 1, 1));
    $tithi = $info->tithi();
    $check('patro info()->tithi()->en', $tithi !== null ? $tithi->en : null, 'Panchami');
    $check('patro info()->isPublicHoliday()', $info->isPublicHoliday(), true);
    $check('patro info()->events()', count($info->events()), 3);
    $check('patro coverage()', NepDate\Patro\coverage()->start()->isoformat(), '2077-01-01');
    $check('patro publicHolidays()', count(NepDate\Patro\publicHolidays()), 294);
    $check('patro event name', $info->events()[0]->name(Lang::Ne), 'नयाँ वर्ष');
}

if ($failures !== []) {
    fwrite(STDERR, "the published packages failed the install check:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, "  - {$failure}\n");
    }
    exit(1);
}

fwrite(STDOUT, "the published packages pass the install check\n");
