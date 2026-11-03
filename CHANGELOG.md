# Changelog

All notable changes to `sanamhub/nepdate` (namespace `NepDate`) and `sanamhub/nepdate-patro`
(namespace `NepDate\Patro`) are documented here. The two packages share this file and a version
number. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions
follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0-rc.1] - 2026-11-03

### Added

- `NepaliDate` with construction (`fromBs`, `fromGregorian`, `today`, `min`, `max`), accessors,
  BS to AD and AD to BS conversion, ordinals, comparisons (`compareTo`, `equals`) and batch
  conversion (`fromGregorianBatch`, `parseBatch`, `toGregorianBatch`).
- The `Month`, `Weekday`, `DateOrder`, `Separator`, `Lang`, `MonthOverflow` and `Quarter` enums,
  and the `NepDateException` hierarchy with a `kind()` accessor.
- Day, month and year arithmetic (`addDays`, `addMonths`, `addYears`, `daysUntil`), `FiscalYear`
  and `NepaliDateRange`.
- Formatting: `toShortString`, `toLongString`, `format`, `__toString`, the month and weekday
  labels, and Devanagari digit rendering.
- Parsing: `NepaliDate::parse`/`tryParse` for the strict form and `parseLenient`/`tryParseLenient`
  for human-written dates with month names, era markers or Devanagari digits.
- Serialization: `JsonSerializable`, `__toString`, and `fromJson`/`fromYmd`/`toYmd`.
- The `nepdate-patro` package (BS 2077 to 2084): `Tithi`, `NepEvent` and `CalendarInfo`, `info()`
  for one day, `coverage()` for the data range, and `publicHolidays()` for the gazetted dates.
- Vector suites for every file in `shared/spec/vectors`: the formatting matrix and pattern
  language, both parse files, the range files, the batch conversions, serialization, the
  `YYYYMMDD` integer, the year lengths and all 7,176 month boundaries.
- `docs/API.md` with an example for every public item, and `composer docs:check` to run them.
- `docs/competitors.md` and `docs/benchmarks.md` with the B1 to B8 measurements and the two PHP
  libraries that pass the benchmark protocol's maturity bar.

### Fixed

- The lenient parser mapped Devanagari digits byte by byte, which corrupted every Devanagari
  month name, and its token scan compared single bytes against a separator list that contains a
  multi-byte character, so `15 साउन 2080` never parsed.
- `format()` dropped a trailing backslash instead of writing it.
- `fromJson()` now reads the JSON document the hook writes, so `json_encode`/`fromJson` round-trip.
- `Calendar::monthLength()` reports an out-of-range year instead of reading past the table.

[Unreleased]: https://github.com/sanamhub/nepdate-php/commits/main
