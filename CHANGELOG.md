# Changelog

All notable changes to `sanamhub/nepdate` (namespace `NepDate`) and `sanamhub/nepdate-patro`
(namespace `NepDate\Patro`) are documented here. The two packages share this file and a version
number. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions
follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/sanamhub/nepdate-php/commits/main
