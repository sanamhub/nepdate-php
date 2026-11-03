# The NepDate PHP API

This is the reference for every public item of `sanamhub/nepdate` (namespace `NepDate`) and
`sanamhub/nepdate-patro` (namespace `NepDate\Patro`). It is grouped like `shared/spec/API.md`, the
inventory of the C# reference, and every group names the spec IDs it covers. Every example below
runs in CI (`composer docs:check`), so the output shown is the output you get.

Two conventions run through the whole API:

- **A date is always valid.** The constructor and the parsers reject anything that is not a real
  day of the calendar, so there is no default or empty date. `NepDateException::kind()` says which
  rule failed.
- **Values are immutable.** Every operation returns a new date or range; nothing is modified in
  place.

Each intentional departure from the C# reference is marked with the `D-xx` row of
`shared/spec/PARITY.md` that allows it.

## Supported dates

The calendar covers **1901-01-01 to 2199-12-30 BS**, which is 1844-04-11 to 2143-04-15 AD. Month
lengths come from the BS table, 29 to 32 days, not from a fixed 30 or 31.

## 1. Dates, identity and comparison

| C# | PHP | Notes |
|---|---|---|
| `NepaliDate(int yearBs, int monthBs, int dayBs)` | `NepaliDate::fromBs(int $year, int $month, int $day): self` | Validates year, then month, then day. |
| `NepaliDate(string rawNepaliDate)` | `NepaliDate::parse(string $text): self` | The strict parser, section 5. |
| `NepaliDate(string rawNepaliDate, bool autoAdjust, bool monthInMiddle = true)` | not ported | `D-05`, `D-06`: no `autoAdjust` heuristics. |
| `NepaliDate(DateTime engDate)` | `NepaliDate::fromGregorian(DateTimeInterface $date): self` | Uses the date part only. |
| `bool IsDefault { get; }` | not ported | `D-01`: no zero value, so no `IsDefault`. |
| `int Year { get; }` | `year(): int` | |
| `int Month { get; }` | `month(): int`, `monthEnum(): Month` | |
| `int Day { get; }` | `day(): int` | |
| `DateTime EnglishDate { get; }` | `toDateTime(): DateTimeImmutable` | Midnight, no zone. |
| `DayOfWeek DayOfWeek { get; }` | `weekday(): Weekday` | |
| `int DayOfYear { get; }` | `dayOfYear(): int` | 1-based in the BS year. |
| `int MonthEndDay { get; }` | `monthLength(): int` | 29 to 32. |
| `NepaliMonths MonthName { get; }` | `monthEnum(): Month` | `Month::label()` and `Month::shortLabel()` name it. |
| `static NepaliDate Today { get; }` | `NepaliDate::today(DateTimeZone $zone): self` | The zone is explicit. |
| `static NepaliDate Now { get; }` | `today(DateTimeZone $zone)` | A date has no time, so there is no separate `now()`. |
| `static readonly NepaliDate MinValue` | `NepaliDate::min(): self` | 1901-01-01 BS. |
| `static readonly NepaliDate MaxValue` | `NepaliDate::max(): self` | 2199-12-30 BS. |
| `NepaliDate MonthEndDate()` | `lastDayOfMonth(): self` | |
| `void Deconstruct(out int year, out int month, out int day)` | `toArray(): array{int, int, int}` | `[year, month, day]`. |
| `bool IsLeapYear()` | `yearLength(): int` | The BS year length, 364 to 367 (`D-12`). |
| `bool IsToday()` | `isToday(DateTimeZone $zone): bool` | |
| `bool IsYesterday()` | `isYesterday(DateTimeZone $zone): bool` | |
| `bool IsTomorrow()` | `isTomorrow(DateTimeZone $zone): bool` | |
| `int CompareTo(NepaliDate obj)` | `compareTo(NepaliDate $other): int` | -1, 0 or 1. |
| `int CompareTo(object obj)` | not ported | Typed comparison only. |
| `bool Equals(NepaliDate other)` | `equals(NepaliDate $other): bool` | |
| `override bool Equals(object obj)` | `equals(NepaliDate $other): bool` | |
| `override int GetHashCode()` | not ported | PHP values have no hash contract. |
| `static bool operator ==(NepaliDate, NepaliDate)` | `equals` | |
| `static bool operator !=(NepaliDate, NepaliDate)` | `!equals` | |
| `static bool operator <(NepaliDate, NepaliDate)` | `compareTo(...) < 0` | |
| `static bool operator <=(NepaliDate, NepaliDate)` | `compareTo(...) <= 0` | |
| `static bool operator >(NepaliDate, NepaliDate)` | `compareTo(...) > 0` | |
| `static bool operator >=(NepaliDate, NepaliDate)` | `compareTo(...) >= 0` | |

```php
$date = NepaliDate::fromBs(2081, 4, 15);
$date->year(); // => 2081
$date->month(); // => 4
$date->day(); // => 15
$date->monthEnum(); // => Month::Shrawan
$date->weekday(); // => Weekday::Tuesday
$date->dayOfYear(); // => 109
$date->monthLength(); // => 32
$date->yearLength(); // => 366
$date->toArray(); // => [2081, 4, 15]
```

The boundaries and the month end:

```php
NepaliDate::min()->isoformat(); // => '1901-01-01'
NepaliDate::max()->isoformat(); // => '2199-12-30'
NepaliDate::min()->toDateTime()->format('Y-m-d'); // => '1844-04-11'
NepaliDate::max()->toDateTime()->format('Y-m-d'); // => '2143-04-15'
NepaliDate::fromBs(2081, 4, 15)->lastDayOfMonth()->isoformat(); // => '2081-04-32'
```

Validation reports the first failure in the order year, month, day:

```php
$kinds = [];
foreach ([[1900, 13, 40], [2081, 13, 40], [2081, 4, 40]] as [$y, $m, $d]) {
    try {
        NepaliDate::fromBs($y, $m, $d);
    } catch (NepDateException $error) {
        $kinds[] = $error->kind();
    }
}
$kinds; // => [ErrorKind::OutOfRange, ErrorKind::InvalidMonth, ErrorKind::InvalidDay]
```

Comparison and equality:

```php
$early = NepaliDate::fromBs(2081, 4, 10);
$late = NepaliDate::fromBs(2081, 4, 20);
$early->compareTo($late); // => -1
$late->compareTo($early); // => 1
$early->compareTo(NepaliDate::fromBs(2081, 4, 10)); // => 0
$early->equals(NepaliDate::fromBs(2081, 4, 10)); // => true
$early->equals($late); // => false
```

The clock methods read the machine clock in the zone you pass, never a hidden local zone:

```php
$zone = new DateTimeZone('Asia/Kathmandu');
$today = NepaliDate::today($zone);
$today->isToday($zone); // => true
$today->addDays(-1)->isYesterday($zone); // => true
$today->addDays(1)->isTomorrow($zone); // => true
NepaliDate::min()->isToday($zone); // => false
```

## 2. Conversion and serialization

| C# | PHP | Notes |
|---|---|---|
| `static NepaliDate DateTimeExtensions.ToNepaliDate(this DateTime englishDate)` | `fromGregorian(DateTimeInterface $date)` | |
| `static NepaliDate SmartParsingExtensions.ToNepaliDate(this string input)` | `parseLenient(string $text): self` | |
| `static bool SmartParsingExtensions.TryToNepaliDate(this string input, out NepaliDate result)` | `tryParseLenient(string $text): ?self` | `null` on failure. |
| `static IEnumerable<NepaliDate> NepaliDate.BulkConvert.ToNepaliDates(IEnumerable<DateTime> engDates, bool useParallel = true)` | `fromGregorianBatch(iterable $dates): array` | Ordered, `list<NepaliDate>`. |
| `static IEnumerable<DateTime> ...ToEnglishDates(IEnumerable<string> nepDates, bool useParallel = true)` | `parseBatch(iterable $texts): array` | Ordered, `list<DateTimeImmutable>`. |
| `static IEnumerable<DateTime> ...ToEnglishDates(IEnumerable<NepaliDate> nepDates, bool useParallel = true)` | `toGregorianBatch(iterable $dates): array` | Ordered, `list<DateTimeImmutable>`. |
| `static IEnumerable<NepaliDate> ...BatchProcessToNepaliDates(IEnumerable<DateTime> engDates, int batchSize = 1000)` | `fromGregorianBatch` | One batch function, not two. |
| `static IEnumerable<DateTime> ...BatchProcessToEnglishDates(IEnumerable<NepaliDate> nepDates, int batchSize = 1000)` | `toGregorianBatch` | One batch function, not two. |
| `sealed class NepaliDateTypeConverter : TypeConverter` | `fromYmd(int $n): self`, `toYmd(): int` | The packed `YYYYMMDD` integer. |
| `class SystemTextJsonConverters.NepaliDateJsonConverter : JsonConverter<NepaliDate>` | `jsonSerialize(): string`, `fromJson(string $json): self` | `D-11`: a quoted `YYYY-MM-DD` string. |
| `class SystemTextJsonConverters.NepaliDateObjectJsonConverter` | not ported | `D-11`: no object form. |
| `class NewtonsoftJsonConverters.NepaliDateJsonConverter : JsonConverter<NepaliDate>` | not ported | `D-11`. |
| `class NewtonsoftJsonConverters.NepaliDateObjectJsonConverter` | not ported | `D-11`. |
| `class NepaliDateXmlSerializer : IXmlSerializable` | not ported | `D-11`: no XML form. |
| `static JsonSerializerOptions SerializationExtensions.ConfigureForNepaliDate(this JsonSerializerOptions options, bool useObjectFormat = false)` | not ported | `D-11`. |
| `static JsonSerializerSettings SerializationExtensions.ConfigureForNepaliDate(this JsonSerializerSettings settings, bool useObjectFormat = false)` | not ported | `D-11`. |

```php
NepaliDate::fromGregorian(new DateTimeImmutable('2024-07-30 23:59:59'))->isoformat(); // => '2081-04-15'
NepaliDate::fromBs(2081, 4, 15)->toDateTime()->format('Y-m-d H:i:s'); // => '2024-07-30 00:00:00'
```

The `YYYYMMDD` integer, both ways:

```php
NepaliDate::fromBs(2081, 4, 15)->toYmd(); // => 20810415
NepaliDate::fromYmd(20810415)->isoformat(); // => '2081-04-15'
NepaliDate::fromYmd(20810433); // => throws NepDateException(InvalidDay)
```

JSON is the native hook, with the canonical text form:

```php
json_encode(NepaliDate::fromBs(2081, 4, 15)); // => '"2081-04-15"'
json_encode(['date' => NepaliDate::fromBs(2081, 4, 15)]); // => '{"date":"2081-04-15"}'
NepaliDate::fromJson('"2081-04-15"')->isoformat(); // => '2081-04-15'
NepaliDate::fromJson('null'); // => throws NepDateException(InvalidCharacter)
```

Batch conversion keeps input order, and one bad item fails the whole call:

```php
$dates = [
    new DateTimeImmutable('1844-04-11'),
    new DateTimeImmutable('2024-07-31'),
    new DateTimeImmutable('2143-04-15'),
];
array_map(static fn (NepaliDate $d): string => $d->isoformat(), NepaliDate::fromGregorianBatch($dates)); // => ['1901-01-01', '2081-04-16', '2199-12-30']
array_map(static fn (DateTimeImmutable $d): string => $d->format('Y-m-d'), NepaliDate::parseBatch(['2081-04-15'])); // => ['2024-07-30']
NepaliDate::fromGregorianBatch([]); // => []
```

## 3. Arithmetic

| C# | PHP | Notes |
|---|---|---|
| `NepaliDate AddMonths(double months, bool awayFromMonthEnd = false)` | `addMonths(int $months, MonthOverflow $overflow = MonthOverflow::Clamp): self` | Whole months; `D-02`. |
| `NepaliDate AddDays(double days)` | `addDays(int $days): self` | Whole days; `D-03`. |
| `NepaliDate AddYears(int years, bool awayFromMonthEnd = false)` | `addYears(int $years, MonthOverflow $overflow = MonthOverflow::Clamp): self` | Same modes. |
| `TimeSpan Subtract(NepaliDate nepDateTo)` | `daysUntil(NepaliDate $other): int`, `diff(NepaliDate $other): DateDiff` | `other - this`; `D-07`. |
| `static TimeSpan operator -(NepaliDate d1, NepaliDate d2)` | no operator | The PHP column: "no operator; use `daysUntil`". |

```php
$date = NepaliDate::fromBs(2081, 4, 32);
$date->addDays(1)->isoformat(); // => '2081-05-01'
$date->addDays(-1)->isoformat(); // => '2081-04-31'
$date->addMonths(2)->isoformat(); // => '2081-06-30'
$date->addMonths(2, MonthOverflow::Spill)->isoformat(); // => '2081-07-02'
$date->addMonths(-2)->isoformat(); // => '2081-02-32'
$date->addYears(1)->isoformat(); // => '2082-04-31'
```

The sign of the day difference has two names (`D-07`): `daysUntil($other)` is `other - this`, and
the C# operator direction, `this - other`, is its negation.

```php
$a = NepaliDate::fromBs(2080, 1, 1);
$b = NepaliDate::fromBs(2081, 4, 15);
$a->daysUntil($b); // => 473
$b->daysUntil($a); // => -473
$a->diff($b)->totalDays; // => 473
```

`diff()` is the years, months and days breakdown of `ALGORITHM.md` §8a:

```php
$diff = NepaliDate::fromBs(2080, 1, 1)->diff(NepaliDate::fromBs(2081, 4, 15));
$diff->negative; // => false
$diff->years; // => 1
$diff->months; // => 3
$diff->days; // => 14
$diff->totalDays; // => 473
NepaliDate::fromBs(2081, 4, 15)->diff(NepaliDate::fromBs(2080, 1, 1))->negative; // => true
```

Arithmetic stops at the data ends with `ErrorKind::OutOfRange`:

```php
NepaliDate::min()->addDays(-1); // => throws NepDateException(OutOfRange)
NepaliDate::max()->addDays(1); // => throws NepDateException(OutOfRange)
NepaliDate::max()->addMonths(1); // => throws NepDateException(OutOfRange)
```

## 4. Formatting

| C# | PHP | Notes |
|---|---|---|
| `override string ToString()` | `__toString(): string` | `YYYY/MM/DD`, ASCII digits. |
| `string ToString(DateFormats dateFormat, Separators separator = Separators.ForwardSlash, bool leadingZeros = true)` | `toShortString(DateOrder $order = Ymd, Separator $separator = Slash, bool $pad = true, Lang $lang = En): string` | |
| `string ToLongDateString(bool leadingZeros = true, bool displayDayName = false, bool displayYear = true)` | `toLongString(bool $weekday = false, bool $year = true, bool $pad = true, Lang $lang = En): string` | |
| `string ToUnicodeString(DateFormats dateFormat = DateFormats.YearMonthDay, Separators separator = Separators.ForwardSlash, bool leadingZeros = true)` | `toShortString(..., Lang::Ne)` | |
| `string ToLongDateUnicodeString(bool leadingZeros = true, bool displayDayName = false, bool displayYear = true)` | `toLongString(..., Lang::Ne)` | |
| `string ToString(string format, IFormatProvider formatProvider = null)` | `format(string $pattern, Lang $lang = En): string` | |
| `bool ISpanFormattable.TryFormat(Span<char>, out int, ReadOnlySpan<char>, IFormatProvider)` | not ported | The PHP column: "none; string return". A string is never too short. |
| `enum DateFormats` | `DateOrder` enum | `Ymd`, `Ydm`, `Myd`, `Mdy`, `Dym`, `Dmy`. |
| `enum Separators` | `Separator` enum | `Slash`, `Backslash`, `Dot`, `Underscore`, `Dash`, `Space`. |
| `enum NepaliMonths` | `Month` enum | `Baishakh` (1) to `Chaitra` (12). |

```php
$date = NepaliDate::fromBs(2081, 4, 15);
(string) $date; // => '2081/04/15'
$date->isoformat(); // => '2081-04-15'
$date->toShortString(DateOrder::Ymd, Separator::Slash, true, Lang::En); // => '2081/04/15'
$date->toShortString(DateOrder::Dmy, Separator::Dash, false, Lang::En); // => '15-4-2081'
$date->toShortString(DateOrder::Mdy, Separator::Dot); // => '04.15.2081'
$date->toShortString(lang: Lang::Ne); // => '२०८१/०४/१५'
```

The long form is `[Weekday, ]Month day[, year]`:

```php
$date = NepaliDate::fromBs(2081, 4, 15);
$date->toLongString(); // => 'Shrawan 15, 2081'
$date->toLongString(weekday: true); // => 'Tuesday, Shrawan 15, 2081'
$date->toLongString(year: false); // => 'Shrawan 15'
$date->toLongString(pad: false); // => 'Shrawan 15, 2081'
$date->toLongString(lang: Lang::Ne); // => 'साउन १५, २०८१'
$date->toLongString(weekday: true, lang: Lang::Ne); // => 'मङ्गलवार, साउन १५, २०८१'
```

Patterns: `yyyy` and longer runs write four digits, `y` to `yyy` write two, `MMMM` the
full name, `MMM` the short name, `MM` and `M` the number, `dd` two digits and any other run of `d`
the number. A backslash escapes the next character, text in single quotes is literal, and a
standard pattern left alone selects the default, the long form or the ISO form.

```php
$date = NepaliDate::fromBs(2081, 4, 15);
$date->format(''); // => '2081/04/15'
$date->format('G'); // => '2081/04/15'
$date->format('s'); // => '2081-04-15'
$date->format('D'); // => 'Shrawan 15, 2081'
$date->format('yyyy-MM-dd'); // => '2081-04-15'
$date->format('M/d/yyyy'); // => '4/15/2081'
$date->format('MMM yyyy'); // => 'Shr 2081'
$date->format('MMMM d, yyyy'); // => 'Shrawan 15, 2081'
$date->format('dd.MM.yy'); // => '15.04.81'
$date->format("yyyy'BS'"); // => '2081BS'
$date->format('yyyy\M\M-\d\d'); // => '2081MM-dd'
$date->format('\\'); // => '\\'
```

`MMM` for Ashad and Ashoj is the one deliberate formatting change: C# writes `Ash` for both, the
port writes `Asa` and `Aso` (`D-04`).

```php
NepaliDate::fromBs(2081, 3, 1)->format('MMM'); // => 'Asa'
NepaliDate::fromBs(2081, 6, 1)->format('MMM'); // => 'Aso'
Month::Ashad->shortLabel(); // => 'Asa'
Month::Ashoj->shortLabel(); // => 'Aso'
Month::Shrawan->label(); // => 'Shrawan'
Month::Shrawan->label(Lang::Ne); // => 'साउन'
Weekday::Tuesday->label(); // => 'Tuesday'
Weekday::Tuesday->label(Lang::Ne); // => 'मङ्गलवार'
Weekday::Tuesday->shortLabel(); // => 'Tue'
Weekday::Tuesday->shortLabel(Lang::Ne); // => 'मङ्गल'
```

The default form is also the ISO form with slashes, and `isoformat()` is the one with dashes:

```php
NepaliDate::fromBs(1901, 1, 1)->isoformat(); // => '1901-01-01'
NepaliDate::fromBs(2199, 12, 30)->isoformat(); // => '2199-12-30'
NepaliDate::fromBs(1901, 1, 1)->toShortString(lang: Lang::Ne); // => '१९०१/०१/०१'
```

## 5. Parsing

| C# | PHP | Notes |
|---|---|---|
| `static NepaliDate Parse(string rawNepaliDate)` | `NepaliDate::parse(string $text): self` | Strict. |
| `static NepaliDate Parse(string rawNepaliDate, bool autoAdjust, bool monthInMiddle = true)` | not ported | `D-05`, `D-06`. |
| `static bool TryParse(string rawNepDate, out NepaliDate result)` | `NepaliDate::tryParse(string $text): ?self` | `null` on failure. |
| `static bool TryParse(string rawNepDate, out NepaliDate result, bool autoAdjust, bool monthInMiddle = true)` | not ported | `D-05`, `D-06`. |
| `static NepaliDate IParsable<NepaliDate>.Parse(string, IFormatProvider)` and `TryParse` | `parse` | `IParsable` has no PHP equivalent; the method is the interface. |
| `static NepaliDate ISpanParsable<NepaliDate>.Parse(ReadOnlySpan<char>, IFormatProvider)` and `TryParse` | `parse` | No span type. |
| `static NepaliDate SmartDateParser.Parse(string input)` | `NepaliDate::parseLenient(string $text): self` | |
| `static bool SmartDateParser.TryParse(string input, out NepaliDate result)` | `NepaliDate::tryParseLenient(string $text): ?self` | |
| `sealed class InvalidNepaliDateFormatException : FormatException` | `NepDateException` with `kind(): ErrorKind` | |

The strict parser takes year, month and day in that order. Separators are `- / . _ \ space | ।`,
leading, trailing and repeated separators are ignored, and Devanagari digits are accepted (`D-05`).

```php
NepaliDate::parse('2080/05/15')->isoformat(); // => '2080-05-15'
NepaliDate::parse('2080.05.15')->isoformat(); // => '2080-05-15'
NepaliDate::parse('2080 05 15')->isoformat(); // => '2080-05-15'
NepaliDate::parse('2080\05\15')->isoformat(); // => '2080-05-15'
NepaliDate::parse('2080।05।15')->isoformat(); // => '2080-05-15'
NepaliDate::parse('  2080 / 05 / 15  ')->isoformat(); // => '2080-05-15'
NepaliDate::parse('२०८०/०५/१५')->isoformat(); // => '2080-05-15'
```

A failure names its kind (`PARSING.md` §4):

```php
$kinds = [];
foreach (['2080/13/01', '2080/05/40', '2080/05', 'abc/def/ghi', '12345/01/01', '1900/12/30'] as $text) {
    try {
        NepaliDate::parse($text);
    } catch (NepDateException $error) {
        $kinds[] = $error->kind();
    }
}
$kinds; // => [ErrorKind::InvalidMonth, ErrorKind::InvalidDay, ErrorKind::WrongGroupCount, ErrorKind::InvalidCharacter, ErrorKind::NumberTooLong, ErrorKind::OutOfRange]
NepaliDate::tryParse('not a date'); // => null
```

The lenient parser falls back to month names, era markers, Devanagari digits and any order of the
three numbers, and never expands a two digit year (`D-06`):

```php
NepaliDate::parseLenient('15 Shrawan 2080')->isoformat(); // => '2080-04-15'
NepaliDate::parseLenient('Shrawan 15, 2080')->isoformat(); // => '2080-04-15'
NepaliDate::parseLenient('15 साउन 2080')->isoformat(); // => '2080-04-15'
NepaliDate::parseLenient('१५ श्रावण २०८०')->isoformat(); // => '2080-04-15'
NepaliDate::parseLenient('2080/04/15 B.S.')->isoformat(); // => '2080-04-15'
NepaliDate::parseLenient('15/05/2080')->isoformat(); // => '2080-05-15'
NepaliDate::tryParseLenient('80/05/15'); // => null
NepaliDate::tryParseLenient('Shrawan Bhadra 2080'); // => null
```

## 6. Ranges

| C# | PHP | Notes |
|---|---|---|
| `readonly struct NepaliDateRange : IEnumerable<NepaliDate>, IEquatable<NepaliDateRange>` | `NepaliDateRange` | Inclusive, never empty, `IteratorAggregate`. |
| `NepaliDateRange(NepaliDate start, NepaliDate end)` | `NepaliDateRange::of(NepaliDate $start, NepaliDate $end): self` | Refuses a start after the end (`D-01`). |
| `NepaliDate Start { get; }` | `start(): NepaliDate` | |
| `NepaliDate End { get; }` | `end(): NepaliDate` | |
| `bool IsEmpty { get; }` | not exposed | `D-01`: ranges are never empty. |
| `int Length { get; }` | `count(): int` | Inclusive number of days. |
| `static NepaliDateRange SingleDay(NepaliDate date)` | `singleDay(NepaliDate $day): self` | |
| `static NepaliDateRange FromDayCount(NepaliDate start, int days)` | `fromDayCount(NepaliDate $start, int $days): self` | |
| `static NepaliDateRange ForMonth(int year, int month)` | `forMonth(int $year, int $month): self` | |
| `static NepaliDateRange ForFiscalYear(int fiscalYear)` | `(new FiscalYear($fy))->range()` | `D-08`. |
| `static NepaliDateRange ForCalendarYear(int year)` | `forYear(int $year): self` | |
| `static NepaliDateRange CurrentMonth()` | `currentMonth(DateTimeZone $zone): self` | |
| `static NepaliDateRange CurrentFiscalYear()` | `currentFiscalYear(DateTimeZone $zone): self` | |
| `static NepaliDateRange CurrentCalendarYear()` | `currentCalendarYear(DateTimeZone $zone): self` | |
| `bool Contains(NepaliDate date)` | `contains(NepaliDate $date): bool` | |
| `bool Contains(NepaliDateRange other)` | `containsRange(NepaliDateRange $other): bool` | |
| `bool Overlaps(NepaliDateRange other)` | `overlaps(NepaliDateRange $other): bool` | |
| `bool IsAdjacentTo(NepaliDateRange other)` | `isAdjacentTo(NepaliDateRange $other): bool` | `D-16`. |
| `NepaliDateRange Intersect(NepaliDateRange other)` | `intersect(NepaliDateRange $other): ?self` | `null` when disjoint; `D-01`, `D-16`. |
| `NepaliDateRange Union(NepaliDateRange other)` | `union(NepaliDateRange $other): self` | The smallest covering range. |
| `NepaliDateRange[] Except(NepaliDateRange other)` | `except(NepaliDateRange $other): array` | Zero, one or two ranges. |
| `NepaliDateRange[] SplitByMonth()` | `splitByMonth(): array` | `D-16`. |
| `NepaliDateRange[] SplitByFiscalQuarter()` | `splitByFiscalQuarter(): array` | `D-16`. |
| `IEnumerable<NepaliDate> DatesWithInterval(int interval)` | `datesWithInterval(int $step = 1): array` | `D-16`. |
| `IEnumerable<NepaliDate> WorkingDays(bool excludeSunday = false)` | `workingDays(bool $excludeSunday = false): array` | `D-16`. |
| `IEnumerable<NepaliDate> WeekendDays(bool includeSunday = true)` | `weekendDays(bool $includeSunday = true): array` | `D-16`. |
| `IEnumerator<NepaliDate> GetEnumerator()` | `getIterator(): Traversable` | Every day, start to end. |
| `override string ToString()` | `__toString(): string` | `start - end`. |
| `string ToString(DateFormats format, Separators separator = Separators.ForwardSlash)` | `toStringWith(DateOrder $order = Ymd, Separator $separator = Slash, Lang $lang = En): string` | `D-15`. |
| `Equals`, `GetHashCode`, `operator ==`, `operator !=` | `equals(NepaliDateRange $other): bool` | Value equality of the bounds. |

```php
$range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));
$range->start()->isoformat(); // => '2081-04-10'
$range->end()->isoformat(); // => '2081-04-20'
$range->count(); // => 11
(string) $range; // => '2081/04/10 - 2081/04/20'
$range->toStringWith(DateOrder::Dmy, Separator::Dash); // => '10-04-2081 - 20-04-2081'
$range->toStringWith(lang: Lang::Ne); // => '२०८१/०४/१० - २०८१/०४/२०'
$range->equals(NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20))); // => true
```

The factories:

```php
NepaliDateRange::singleDay(NepaliDate::fromBs(2081, 4, 15))->count(); // => 1
NepaliDateRange::fromDayCount(NepaliDate::fromBs(2081, 4, 15), 5)->count(); // => 5
NepaliDateRange::forMonth(2081, 4)->count(); // => 32
NepaliDateRange::forYear(2081)->count(); // => 366
(new FiscalYear(2081))->range()->count(); // => 366
NepaliDateRange::fromDayCount(NepaliDate::fromBs(2081, 4, 15), 0); // => throws NepDateException(OutOfRange)
NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 20), NepaliDate::fromBs(2081, 4, 10)); // => throws NepDateException(OutOfRange)
```

Membership and the relations:

```php
$range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));
$range->contains(NepaliDate::fromBs(2081, 4, 10)); // => true
$range->contains(NepaliDate::fromBs(2081, 4, 21)); // => false
$range->containsRange(NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 12), NepaliDate::fromBs(2081, 4, 18))); // => true
$range->overlaps(NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 15), NepaliDate::fromBs(2081, 4, 25))); // => true
$range->isAdjacentTo(NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 21), NepaliDate::fromBs(2081, 4, 25))); // => true
$range->isAdjacentTo(NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 22), NepaliDate::fromBs(2081, 4, 25))); // => false
```

The set operations:

```php
$range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));
$range->intersect(NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 15), NepaliDate::fromBs(2081, 4, 25)))->count(); // => 6
$range->intersect(NepaliDateRange::of(NepaliDate::fromBs(2081, 5, 1), NepaliDate::fromBs(2081, 5, 5))); // => null
$range->union(NepaliDateRange::of(NepaliDate::fromBs(2081, 5, 1), NepaliDate::fromBs(2081, 5, 5)))->count(); // => 28
count($range->except(NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 13), NepaliDate::fromBs(2081, 4, 17)))); // => 2
count($range->except($range)); // => 0
count($range->except(NepaliDateRange::of(NepaliDate::fromBs(2081, 5, 1), NepaliDate::fromBs(2081, 5, 5)))); // => 1
```

The splits clip the first and last piece to the range ends:

```php
$range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 30), NepaliDate::fromBs(2081, 6, 2));
array_map(static fn (NepaliDateRange $r): string => (string) $r, $range->splitByMonth()); // => ['2081/04/30 - 2081/04/32', '2081/05/01 - 2081/05/31', '2081/06/01 - 2081/06/02']
count(NepaliDateRange::forYear(2081)->splitByMonth()); // => 12
count(NepaliDateRange::of(NepaliDate::fromBs(2081, 3, 30), NepaliDate::fromBs(2081, 7, 2))->splitByFiscalQuarter()); // => 3
```

The day helpers, and iteration:

```php
$range = NepaliDateRange::of(NepaliDate::fromBs(2081, 4, 10), NepaliDate::fromBs(2081, 4, 20));
count($range->datesWithInterval(3)); // => 4
$range->datesWithInterval(11)[0]->isoformat(); // => '2081-04-10'
count($range->workingDays()); // => 9
count($range->workingDays(true)); // => 7
count($range->weekendDays()); // => 4
count($range->weekendDays(false)); // => 2
count(iterator_to_array($range, false)); // => 11
```

Every helper stops at the last date instead of throwing, so a range that reaches 2199-12-30 still
answers (`D-16`):

```php
$tail = NepaliDateRange::of(NepaliDate::max(), NepaliDate::max());
count(iterator_to_array($tail, false)); // => 1
count($tail->datesWithInterval(1)); // => 1
count($tail->splitByMonth()); // => 1
$tail->isAdjacentTo(NepaliDateRange::singleDay(NepaliDate::fromBs(2081, 4, 10))); // => false
```

## 7. Fiscal year

| C# | PHP | Notes |
|---|---|---|
| `enum FiscalYearQuarters` | `Quarter` enum | `Q1` to `Q4`. |
| `NepaliDate FiscalYearStartDate(int yearOffset = 0)` | `FiscalYear::containing($date)->start()` | `D-08` replaces the instance offsets. |
| `NepaliDate FiscalYearEndDate(int yearOffset = 0)` | `->end()` | |
| `(NepaliDate startDate, NepaliDate endDate) FiscalYearStartAndEndDate(int yearOffset = 0)` | `->range()` | |
| `NepaliDate FiscalYearQuarterStartDate(FiscalYearQuarters q = Current, int yearOffset = 0)` | `->quarterRange(Quarter $quarter)->start()` | |
| `NepaliDate FiscalYearQuarterEndDate(FiscalYearQuarters q = Current, int yearOffset = 0)` | `->quarterRange($quarter)->end()` | |
| `(NepaliDate startDate, NepaliDate endDate) FiscalYearQuarterStartAndEndDate(FiscalYearQuarters q = Current, int yearOffset = 0)` | `->quarterRange($quarter)` | |
| `static NepaliDate GetFiscalYearStartDate(int fiscalYear)` | `(new FiscalYear($fy))->start()` | |
| `static NepaliDate GetFiscalYearEndDate(int fiscalYear)` | `(new FiscalYear($fy))->end()` | |
| `static (NepaliDate, NepaliDate) GetFiscalYearStartAndEndDate(int fiscalYear)` | `(new FiscalYear($fy))->range()` | |
| `static NepaliDate GetFiscalYearQuarterStartDate(int fiscalYear, int month)` | not ported | `D-08`: the fiscal-year value covers it. |
| `static NepaliDate GetFiscalYearQuarterEndDate(int fiscalYear, int month)` | not ported | `D-08`. |
| `static (NepaliDate, NepaliDate) GetFiscalYearQuarterStartAndEndDate(int fiscalYear, int month)` | not ported | `D-08`. |

A fiscal year starts on 1 Shrawan and ends on the last day of Ashad of the next BS year. For a date
in months 1 to 3 the containing fiscal year started in the previous BS year.

| Quarter | Months |
|---|---|
| `Quarter::Q1` | Shrawan to Ashoj (4 to 6) |
| `Quarter::Q2` | Kartik to Poush (7 to 9) |
| `Quarter::Q3` | Magh to Chaitra (10 to 12) |
| `Quarter::Q4` | Baishakh to Ashad of the next year (1 to 3) |

```php
$fiscal = FiscalYear::containing(NepaliDate::fromBs(2081, 4, 15));
$fiscal->startYear; // => 2081
$fiscal->start()->isoformat(); // => '2081-04-01'
$fiscal->end()->isoformat(); // => '2082-03-32'
$fiscal->range()->count(); // => 366
$fiscal->label(); // => '2081/82'
$fiscal->label(Lang::Ne); // => '२०८१/८२'
FiscalYear::containing(NepaliDate::fromBs(2081, 1, 1))->startYear; // => 2080
```

```php
$fiscal = new FiscalYear(2081);
$fiscal->quarterRange(Quarter::Q1)->start()->isoformat(); // => '2081-04-01'
$fiscal->quarterRange(Quarter::Q1)->end()->isoformat(); // => '2081-06-30'
$fiscal->quarterRange(Quarter::Q2)->start()->isoformat(); // => '2081-07-01'
$fiscal->quarterRange(Quarter::Q3)->end()->isoformat(); // => '2081-12-31'
$fiscal->quarterRange(Quarter::Q4)->start()->isoformat(); // => '2082-01-01'
$fiscal->quarterRange(Quarter::Q4)->end()->isoformat(); // => '2082-03-32'
$fiscal->equals(new FiscalYear(2081)); // => true
(string) $fiscal; // => '2081/82'
```

`NepaliDate::fiscalYear()` and `NepaliDate::quarter()` are the two shortcuts onto the same values:

```php
NepaliDate::fromBs(2081, 4, 15)->fiscalYear()->label(); // => '2081/82'
NepaliDate::fromBs(2081, 4, 15)->quarter(); // => Quarter::Q1
NepaliDate::fromBs(2081, 7, 1)->quarter(); // => Quarter::Q2
NepaliDate::fromBs(2081, 10, 1)->quarter(); // => Quarter::Q3
NepaliDate::fromBs(2081, 1, 1)->quarter(); // => Quarter::Q4
```

## 8. Patro

The patro lives in `sanamhub/nepdate-patro`, namespace `NepDate\Patro`, and depends on the core
package. `D-13` keeps it out of core: the core package has no tithi, holiday or event data.

| C# | PHP | Notes |
|---|---|---|
| `readonly struct CalendarInfo` | `CalendarInfo` | Immutable snapshot. |
| `string TithiNp { get; }` | `info($date)->tithi()?->name(Lang::Ne)` | `null` outside the tithi data. |
| `string TithiEn { get; }` | `info($date)->tithi()?->name(Lang::En)` | |
| `bool IsPublicHoliday { get; }` | `info($date)->isPublicHoliday()` | |
| `string[] EventsNp { get; }` | `info($date)->events()` with `name(Lang::Ne)` | File order, empty array when none. |
| `string[] EventsEn { get; }` | `info($date)->events()` with `name(Lang::En)` | |
| `CalendarInfo GetCalendarInfo()` | `info(NepaliDate $date): CalendarInfo` | A free function. |

`info()` never fails: a date without data returns a `null` tithi, `false` and an empty list.

```php
$info = info(NepaliDate::fromBs(2081, 1, 1));
$info->tithi()->name(); // => 'Panchami'
$info->tithi()->name(Lang::Ne); // => 'पञ्चमी'
$info->isPublicHoliday(); // => true
array_map(static fn ($event): string => $event->name(), $info->events()); // => ['New Year', 'Mesh Sankranti', 'Bisket Jatra']
$info->events()[0]->name(Lang::Ne); // => 'नयाँ वर्ष'
```

```php
$empty = info(NepaliDate::fromBs(2070, 6, 15));
$empty->tithi(); // => null
$empty->isPublicHoliday(); // => false
$empty->events(); // => []
```

`coverage()` is the smallest BS range with any data and `publicHolidays()` lists every gazetted
holiday in ascending order:

```php
coverage()->start()->isoformat(); // => '2077-01-01'
coverage()->end()->isoformat(); // => '2084-08-20'
count(publicHolidays()); // => 294
publicHolidays()[0]->isoformat(); // => '2077-01-01'
info(publicHolidays()[0])->isPublicHoliday(); // => true
```

## The enums

| Enum | Cases |
|---|---|
| `Month` (int, 1 to 12) | `Baishakh`, `Jestha`, `Ashad`, `Shrawan`, `Bhadra`, `Ashoj`, `Kartik`, `Mangsir`, `Poush`, `Magh`, `Falgun`, `Chaitra` |
| `Weekday` (int, 0 to 6) | `Sunday`, `Monday`, `Tuesday`, `Wednesday`, `Thursday`, `Friday`, `Saturday` |
| `Quarter` (int, 1 to 4) | `Q1`, `Q2`, `Q3`, `Q4` |
| `DateOrder` | `Ymd`, `Ydm`, `Myd`, `Mdy`, `Dym`, `Dmy` |
| `Separator` (string) | `Slash`, `Backslash`, `Dot`, `Underscore`, `Dash`, `Space` |
| `Lang` | `En`, `Ne` |
| `MonthOverflow` | `Clamp`, `Spill` |
| `ErrorKind` (string) | `OutOfRange`, `InvalidMonth`, `InvalidDay`, `InvalidGregorian`, `InvalidCharacter`, `WrongGroupCount`, `NumberTooLong`, `Ambiguous`, `Unrecognized` |

```php
Month::Baishakh->value; // => 1
Month::Chaitra->value; // => 12
Weekday::Sunday->value; // => 0
Weekday::Saturday->value; // => 6
Separator::Backslash->value; // => '\\'
Quarter::Q1->value; // => 1
ErrorKind::InvalidDay->value; // => 'invalid_day'
```

## Rows that are not ported

`shared/spec/API.md` keeps these C# members outside the port surface, and each keeps its reason:

| C# | Why |
|---|---|
| The `autoAdjust` constructor and parse overloads | `D-05`, `D-06`: replaced by the strict and the lenient parser. |
| `IsDefault` | `D-01`: a PHP value cannot be an invalid date, so there is no zero value. |
| `CompareTo(object)`, `GetHashCode()` | The language has typed comparison and no hash contract. |
| The object JSON form, the Newtonsoft and XML adapters, the configuration helpers | `D-11`: the native hook and the `YYYYMMDD` integer only. |
| `TryFormat` | A PHP string is never too short, so formatting returns a string. |
| The static `(year, month)` fiscal quarter helpers | `D-08`: the fiscal-year value covers them. |
| `IsEmpty` | `D-01`: ranges are never empty. |

The C# `-` operator has no PHP member: PHP has no operator overloading, so `daysUntil` is the only
day difference, and its direction is `other - this` (`D-07`).

## Conventions

- `NepaliDate` is the only date type; a range and a fiscal year are built from dates, never from
  strings.
- Every clock method takes a `DateTimeZone`; nothing reads a hidden local zone.
- The optional arguments default to the C# behaviour, so the short call is the common one:
  `MonthOverflow::Clamp`, `DateOrder::Ymd`, `Separator::Slash`, `Lang::En`, padding on.

## Errors

`NepDateException` extends `RuntimeException` and carries a `kind()`:

```php
try {
    NepaliDate::fromBs(2081, 4, 33);
} catch (NepDateException $error) {
    $error->kind(); // => ErrorKind::InvalidDay
    $error instanceof RuntimeException; // => true
    $error->getMessage() !== ''; // => true
}
```
