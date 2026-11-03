<p align="center">
  <img src="https://raw.githubusercontent.com/sanamhub/nepdate-php/main/docs/assets/nepdate-php-128.png" width="128" alt="NepDate for PHP logo">
</p>

<h1 align="center">nepdate-php</h1>

<p align="center">
  <a href="https://github.com/sanamhub/nepdate-php/actions/workflows/ci.yml"><img src="https://github.com/sanamhub/nepdate-php/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="https://packagist.org/packages/sanamhub/nepdate"><img src="https://img.shields.io/packagist/v/sanamhub/nepdate?include_prereleases&label=sanamhub%2Fnepdate&logo=packagist&logoColor=white" alt="sanamhub/nepdate on Packagist"></a>
  <a href="https://packagist.org/packages/sanamhub/nepdate-patro"><img src="https://img.shields.io/packagist/v/sanamhub/nepdate-patro?include_prereleases&label=sanamhub%2Fnepdate-patro&logo=packagist&logoColor=white" alt="sanamhub/nepdate-patro on Packagist"></a>
  <img src="https://img.shields.io/badge/php-%5E8.3-777BB4?logo=php&logoColor=white" alt="PHP ^8.3">
  <a href="https://github.com/sanamhub/nepdate-php/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue" alt="License: MIT"></a>
</p>

Bikram Sambat (Nepali calendar) dates for PHP: conversion to and from Gregorian, arithmetic,
formatting, parsing, ranges and fiscal years. Part of the NepDate family: the shared data and spec
live in the hub [`sanamhub/nepdate`](https://github.com/sanamhub/nepdate); the C# original is
[NepDate](https://github.com/RajuPrasai/NepDate).

> **Status:** 0.2.0-rc.1 is the first release of this port and is not tagged yet. The API is
> listed in [docs/API.md](https://github.com/sanamhub/nepdate-php/blob/main/docs/API.md) and may
> still change before 0.2.0. Patro data (tithi, holidays, events) is informational, not an official
> calendar.

- Two Composer packages, released together with the same version: `sanamhub/nepdate` (namespace
  `NepDate`) and `sanamhub/nepdate-patro` (namespace `NepDate\Patro`) for tithi, public holidays
  and events. The core carries no calendar data beyond the month lengths.
- Pure PHP 8.3, no extension, no runtime dependency and no runtime data file.
- BS 1901-01-01 to 2199-12-30 (AD 1844-04-11 to 2143-04-15). A date is always a real day of the
  calendar; there is no invalid or default date. Same results as C# NepDate and the other ports,
  checked against the shared vectors.
- Framework-agnostic: Laravel, Symfony and plain PHP need no bridge and no service provider.

## Install

```sh
composer require "sanamhub/nepdate:^0.2@rc"
```

The `@rc` flag is needed while only the release candidate exists; once 0.2.0 is out,
`composer require sanamhub/nepdate` is enough.

## Quick start

```php
$date = NepaliDate::fromBs(2081, 4, 15);
(string) $date; // => '2081/04/15'
$date->toDateTime()->format('Y-m-d'); // => '2024-07-30'
$date->toLongString(weekday: true); // => 'Tuesday, Shrawan 15, 2081'
```

Every class lives in the `NepDate` namespace (`use NepDate\NepaliDate;`). The examples in this
file are run and checked on every pull request.

## Dates

The constructor validates the year first, then the month, then the day, and throws a
`NepDateException` whose `kind()` says which rule failed.

```php
$date = NepaliDate::fromBs(2081, 4, 32);
$date->year(); // => 2081
$date->month(); // => 4
$date->day(); // => 32
$date->weekday(); // => Weekday::Friday
$date->dayOfYear(); // => 126
$date->monthLength(); // => 32
$date->yearLength(); // => 366
(string) NepaliDate::min(); // => '1901/01/01'
(string) NepaliDate::max(); // => '2199/12/30'
```

The month enum and the year length replace the C# `MonthName` and `IsLeapYear()`. The length is the
BS year length, 364 to 367 days (`D-12`), not a Gregorian leap year.

```php
$date = NepaliDate::fromBs(2081, 4, 15);
$date->monthEnum(); // => Month::Shrawan
$date->monthEnum()->label(); // => 'Shrawan'
$date->monthEnum()->shortLabel(Lang::Ne); // => 'साउन'
(string) $date->lastDayOfMonth(); // => '2081/04/32'
$date->toArray(); // => [2081, 4, 15]
$date->fiscalYear()->label(); // => '2081/82'
```

Comparison uses the calendar order. `compareTo` returns -1, 0 or 1, and `equals` is value
equality.

```php
$early = NepaliDate::fromBs(2081, 4, 10);
$late = NepaliDate::fromBs(2081, 4, 20);
$early->compareTo($late); // => -1
$early->equals(NepaliDate::fromBs(2081, 4, 10)); // => true
$early->toArray() === $late->toArray(); // => false
```

## Conversion and serialization

Convert a `DateTimeInterface` by its date part; the time of day and the zone are ignored. The
canonical text form is `YYYY-MM-DD`, and the packed `YYYYMMDD` integer works both ways.

```php
NepaliDate::fromGregorian(new DateTimeImmutable('2024-07-30'))->isoformat(); // => '2081-04-15'
NepaliDate::fromYmd(20810415)->isoformat(); // => '2081-04-15'
NepaliDate::fromBs(2081, 4, 15)->toYmd(); // => 20810415
json_encode(NepaliDate::fromBs(2081, 4, 15)); // => '"2081-04-15"'
NepaliDate::fromJson('"2081-04-15"')->isoformat(); // => '2081-04-15'
```

Batch conversion keeps input order, and one bad item fails the whole call.

```php
NepaliDate::fromGregorianBatch([new DateTimeImmutable('2024-07-30')])[0]->isoformat(); // => '2081-04-15'
NepaliDate::parseBatch(['2081-04-15'])[0]->format('Y-m-d'); // => '2024-07-30'
NepaliDate::toGregorianBatch([NepaliDate::min()])[0]->format('Y-m-d'); // => '1844-04-11'
```

## Arithmetic

Months are whole numbers. When the day does not exist in the target month, `MonthOverflow::Clamp`
(the default) uses the last day and `MonthOverflow::Spill` carries the extra days into the next
month. A day difference is a signed integer: `daysUntil($other)` is `other - this` (`D-07`).

```php
$date = NepaliDate::fromBs(2081, 4, 32);
$date->addDays(30)->isoformat(); // => '2081-05-30'
$date->addMonths(2)->isoformat(); // => '2081-06-30'
$date->addMonths(2, MonthOverflow::Spill)->isoformat(); // => '2081-07-02'
$date->addYears(1)->isoformat(); // => '2082-04-31'
$date->daysUntil(NepaliDate::fromBs(2081, 5, 1)); // => 1
$date->diff(NepaliDate::fromBs(2081, 5, 31))->days; // => 0
```

`diff` returns the years, months and days breakdown of
[`ALGORITHM.md`](https://github.com/sanamhub/nepdate/blob/main/spec/ALGORITHM.md) §8a.

```php
$diff = NepaliDate::fromBs(2080, 1, 1)->diff(NepaliDate::fromBs(2081, 4, 15));
$diff->years; // => 1
$diff->months; // => 3
$diff->days; // => 14
$diff->totalDays; // => 473
$diff->negative; // => false
```

## Formatting

Short formats take an order, a separator, a padding flag and a language. The long form is
`[Weekday, ]Month day[, year]`. Nepali output uses Devanagari names and digits.

```php
$date = NepaliDate::fromBs(2081, 4, 15);
(string) $date; // => '2081/04/15'
$date->isoformat(); // => '2081-04-15'
$date->toShortString(DateOrder::Dmy, Separator::Dash); // => '15-04-2081'
$date->toShortString(DateOrder::Ymd, Separator::Slash, false); // => '2081/4/15'
$date->toShortString(lang: Lang::Ne); // => '२०८१/०४/१५'
$date->toLongString(); // => 'Shrawan 15, 2081'
$date->toLongString(weekday: true, lang: Lang::Ne); // => 'मङ्गलवार, साउन १५, २०८१'
$date->toLongString(pad: false); // => 'Shrawan 15, 2081'
```

Patterns accept `yyyy` (four-digit year), `yy` (two-digit), `MMMM` (full month name), `MMM` (short
name), `MM`, `M`, `dd`, `d`, a backslash escape and a quoted literal.

```php
$date = NepaliDate::fromBs(2081, 4, 15);
$date->format('yyyy-MM-dd'); // => '2081-04-15'
$date->format('d-M-yyyy'); // => '15-4-2081'
$date->format('MMM yyyy'); // => 'Shr 2081'
$date->format("yyyy'BS'"); // => '2081BS'
$date->format('yyyy\M\M-\d\d'); // => '2081MM-dd'
```

## Parsing

The strict parser takes year, month and day in that order with `- / . _ \ space | ।` as separators,
and accepts Devanagari digits (`D-05`). The lenient parser also reads month names, era markers and
any order of the three numbers.

```php
NepaliDate::parse('2081/04/15')->isoformat(); // => '2081-04-15'
NepaliDate::parse('२०८१/०४/१५')->isoformat(); // => '2081-04-15'
NepaliDate::tryParse('not a date'); // => null
NepaliDate::parseLenient('15 Shrawan 2081')->isoformat(); // => '2081-04-15'
NepaliDate::parseLenient('Shrawan 15, 2081')->isoformat(); // => '2081-04-15'
NepaliDate::tryParseLenient('2080/13/01'); // => null
```

A failure carries the error kind of
[`PARSING.md`](https://github.com/sanamhub/nepdate/blob/main/spec/PARSING.md) §4, so a caller can
match on it.

```php
try {
    NepaliDate::parse('2081/09/30');
} catch (NepDateException $error) {
    $error->kind(); // => ErrorKind::InvalidDay
}
```

## Ranges

A range is inclusive and never empty: it refuses a start after its end. `union` is the smallest
covering range, `intersect` returns `null` when there is no overlap, and `except` returns zero, one
or two ranges.

```php
$range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));
$range->count(); // => 11
$range->contains(NepaliDate::fromBs(2081, 4, 15)); // => true
$range->overlaps(NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 15), NepaliDate::fromBs(2081, 5, 5))); // => true
(string) $range; // => '2081/04/10 - 2081/04/20'
$range->toStringWith(DateOrder::Dmy, Separator::Dash); // => '10-04-2081 - 20-04-2081'
count($range->splitByMonth()); // => 1
```

The factories cover a month, a calendar year and a fiscal year, and the day helpers cover
intervals, working days and weekend days.

```php
NepaliDateRange::forMonth(2081, 4)->count(); // => 32
NepaliDateRange::forYear(2081)->count(); // => 366
(new FiscalYear(2081))->range()->count(); // => 366
count(NepaliDateRange::forMonth(2081, 4)->workingDays()); // => 28
count(NepaliDateRange::forMonth(2081, 4)->weekendDays()); // => 8
count(NepaliDateRange::singleDay(NepaliDate::fromBs(2081, 4, 15))->datesWithInterval(1)); // => 1
```

Iterating a range yields every day from its start to its end, including 2199-12-30 (`D-16`).

```php
$days = [];
foreach (NepaliDateRange::forMonth(2081, 5) as $day) {
    $days[] = $day->isoformat();
}
count($days); // => 31
$days[0]; // => '2081-05-01'
```

## Fiscal year

A fiscal year starts on 1 Shrawan and ends on the last day of Ashad of the next BS year. Quarters
are Q1 Shrawan to Ashoj, Q2 Kartik to Poush, Q3 Magh to Chaitra and Q4 Baishakh to Ashad.

```php
$fiscal = FiscalYear::containing(NepaliDate::fromBs(2081, 4, 15));
$fiscal->startYear; // => 2081
$fiscal->start()->isoformat(); // => '2081-04-01'
$fiscal->end()->isoformat(); // => '2082-03-32'
$fiscal->quarterRange(Quarter::Q1)->end()->isoformat(); // => '2081-06-30'
$fiscal->quarterRange(Quarter::Q4)->start()->isoformat(); // => '2082-01-01'
```

## The clock

Nothing reads a hidden local zone. Every clock method takes the zone explicitly.

```php
$zone = new DateTimeZone('Asia/Kathmandu');
$today = NepaliDate::today($zone);
$today->isToday($zone); // => true
$today->addDays(-1)->isYesterday($zone); // => true
$today->addDays(1)->isTomorrow($zone); // => true
```

## Patro: tithi, holidays and events

The core has no patro data. Install both packages:

```sh
composer require "sanamhub/nepdate:^0.2@rc" "sanamhub/nepdate-patro:^0.2@rc"
```

The functions live in the `NepDate\Patro` namespace (`use function NepDate\Patro\info;`) and answer
one day at a time. The data covers 2077-01-01 to 2084-08-20 BS; every other date answers with no
tithi, no holiday and no events, and never fails.

```php
$info = info(NepaliDate::fromBs(2081, 1, 1));
$info->tithi()->name(); // => 'Panchami'
$info->tithi()->name(Lang::Ne); // => 'पञ्चमी'
$info->isPublicHoliday(); // => true
$info->events()[0]->name(); // => 'New Year'
$info->events()[0]->name(Lang::Ne); // => 'नयाँ वर्ष'
```

`coverage()` is the range the data covers and `publicHolidays()` lists every gazetted holiday.

```php
coverage()->start()->isoformat(); // => '2077-01-01'
coverage()->end()->isoformat(); // => '2084-08-20'
count(publicHolidays()); // => 294
```

## API

The complete signatures, defaults and exceptions, with an example for every public item, are in
the [API reference](https://github.com/sanamhub/nepdate-php/blob/main/docs/API.md).

| Namespace | Public names |
|---|---|
| `NepDate` | `NepaliDate`, `NepaliDateRange`, `FiscalYear`, `DateDiff`, `Month`, `Weekday`, `Quarter`, `Lang`, `DateOrder`, `Separator`, `MonthOverflow`, `ErrorKind`, `NepDateException` |
| `NepDate\Patro` | `CalendarInfo`, `Tithi`, `NepEvent`, `info()`, `coverage()`, `publicHolidays()` |

The design in short:

- Immutable value objects; every operation returns a new date.
- Errors are exceptions with a `kind()`, never a boolean or a silently wrong date.
- The calendar tables are generated from the hub's data at build time and committed as PHP
  constants; nothing is read at runtime
  ([ADR-0003](https://github.com/sanamhub/nepdate-php/blob/main/docs/adr/0003-generated-tables.md)).
- Every behavioural decision traces to the shared spec and to the golden vectors under
  `shared/spec/vectors/`, 29,000 of which run in CI on every pull request.

## Supported versions

- PHP 8.3, 8.4 and 8.5, tested on Linux, macOS and Windows.
- The current version line is `0.2`. Both packages share one version number, and the other
  [NepDate ports](https://github.com/sanamhub/nepdate#packages) follow the same line.
- Security fixes go to the latest release only; see
  [SECURITY.md](https://github.com/sanamhub/nepdate-php/blob/main/SECURITY.md).

## Documentation

| Page | What it covers |
|---|---|
| [API](https://github.com/sanamhub/nepdate-php/blob/main/docs/API.md) | The complete public surface, with an example for every item |
| [Benchmarks](https://github.com/sanamhub/nepdate-php/blob/main/docs/benchmarks.md) | B1 to B8 against the other PHP libraries and C# NepDate |
| [Competitors](https://github.com/sanamhub/nepdate-php/blob/main/docs/competitors.md) | The PHP libraries in the benchmark |
| [ADRs](https://github.com/sanamhub/nepdate-php/blob/main/docs/adr/README.md) | Why the design is what it is |
| [Release runbook](https://github.com/sanamhub/nepdate-php/blob/main/docs/runbooks/release.md) | How a version is checked, tagged and published |
| [Changelog](https://github.com/sanamhub/nepdate-php/blob/main/CHANGELOG.md) | What changed in each version |
| [Data error form](https://github.com/sanamhub/nepdate/issues/new/choose) | A wrong date, holiday or tithi, reported in the hub |
| [Spec](https://github.com/sanamhub/nepdate/tree/main/spec) | Behaviour and vectors, in the hub |
| [Other languages](https://github.com/sanamhub/nepdate#packages) | The same library for C#, Python, TypeScript, Rust, Go, C and more |

## Benchmarks

`bench/run.php` measures B1 to B8 of the shared benchmark protocol over the shared 64 dates, next to
the two mature PHP libraries listed in
[docs/competitors.md](https://github.com/sanamhub/nepdate-php/blob/main/docs/competitors.md). The
current figures are preliminary; the method, the machine and the numbers are in
[docs/benchmarks.md](https://github.com/sanamhub/nepdate-php/blob/main/docs/benchmarks.md).

## Contributing

Setup, the `composer ci` gate, the executable documentation and the commit style are in
[CONTRIBUTING.md](https://github.com/sanamhub/nepdate-php/blob/main/CONTRIBUTING.md). This project
follows the
[Code of Conduct](https://github.com/sanamhub/nepdate-php/blob/main/CODE_OF_CONDUCT.md). Report a
vulnerability privately as
[SECURITY.md](https://github.com/sanamhub/nepdate-php/blob/main/SECURITY.md) says, and a data error
in the hub.

## Credit

NepDate for PHP is a port of [NepDate](https://github.com/RajuPrasai/NepDate), the C# library by
Raju Prasai and contributors, and follows the same conversion tables. The calendar data comes from
the hub [`sanamhub/nepdate`](https://github.com/sanamhub/nepdate), which derives it from that
project; see [NOTICE](https://github.com/sanamhub/nepdate-php/blob/main/NOTICE).

## License

MIT ([LICENSE](https://github.com/sanamhub/nepdate-php/blob/main/LICENSE)). Calendar data from
NepDate by Raju Prasai and contributors, MIT
([NOTICE](https://github.com/sanamhub/nepdate-php/blob/main/NOTICE)). Not affiliated with the
Government of Nepal.
