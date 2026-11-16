# ADR-0008: one month-start table, and the fast paths built on it

- Status: accepted
- Date: 2026-11-15
- Changes: the calendar table layout of [ADR-0003](0003-generated-tables.md), decision 1. The rest
  of ADR-0003 stands.

## Context

The first benchmark run (`docs/benchmarks.md`, marked preliminary) put this port 70 to 200 times
behind the C# library, far more than the interpreter explains. Profiling each operation found the
cost in a few places:

| Where | What it did |
|---|---|
| `Calendar::toSerial` | walked the months of the year and called `monthLength` for each |
| `Calendar::fromSerial` | binary search over the years, then the same walk over the months |
| `NepaliDate::fromGregorian` | called `DateTimeInterface::format` three times and cast the strings |
| `NepaliDate::toDateTime` | built a string with `sprintf` and let `DateTimeImmutable` parse it |
| `Names::dayDigits`, `yearDigits` | padded and translated the digits of every number on every call |
| `Parse::strict` | read `YYYY/MM/DD` one byte at a time in userland |
| `info()` | built new `Tithi`, `NepEvent` and `CalendarInfo` objects on every call |

The packed month lengths of ADR-0003 are small, but every conversion has to unpack them in a loop.
The other ports of the family keep one array of month starts and read it once per conversion.

## Decision

1. **`CalendarTable` holds one array, `MONTH_START`**: the serial of the first day of each of the
   3,588 months, then the total day count. A month's length is the difference of two neighbours, a
   year's start is every twelfth entry, and the serial of a date is one read plus the day. The
   packed lengths and the year-start array are gone. As a class constant the array lives in
   OPcache's shared memory; without OPcache the file costs about 0.1 ms to compile once per process.
2. **Serial to date estimates the month by proportion** (`serial * MONTHS / TOTAL_DAYS`) and
   corrects it by a step. A test checks every one of the 109,212 serials.
3. **`fromGregorian` reads the day from the timestamp**: seconds since the epoch plus the value's
   own zone offset, floored to days. That is the day on the value's wall clock, the same day the
   three `format` calls gave. A `DateTimeInterface` is always a real date, so the
   `InvalidGregorian` check it could never fail is removed from this path.
4. **`toDateTime` sets the date on a kept midnight** in the default time zone. The kept value is
   rebuilt when the default time zone changes, so the result is what parsing `YYYY-MM-DD` gave:
   midnight of that day in the default zone.
5. **Digits come from constant tables** for `00` to `99` in both scripts. A year is two table reads.
6. **`Parse::strict` reads the canonical shape with native calls**: ten bytes, ASCII separators at
   positions 4 and 7, digits elsewhere. Every other input, Devanagari digits included, takes the
   scanner, which gives the same answer for the canonical shape.
7. **`info()` keeps one `CalendarInfo` per day.** The values are immutable, so handing out the same
   object is not observable except by identity. At most 2,793 are kept, one per day of the coverage.

## Consequences

Measured on the reference machine with `php bench/run.php --iterations=20`, pinned to one core,
before and after (ns per operation, including the harness's own call):

| ID | Operation | Before | After |
|---|---|---:|---:|
| B1 | BS to AD | 1468 | 985 |
| B2 | AD to BS | 2337 | 684 |
| B3 | add 1000 days | 2035 | 884 |
| B4 | add 13 months | 1712 | 723 |
| B5 | long Nepali date | 1055 | 624 |
| B6 | strict parse | 2524 | 1082 |
| B7 | patro info | 1979 | 351 |
| B8 | patro month grid | 111942 | 24046 |

- No public method changes its result. `NepaliDate::fromGregorian` reports an out-of-range date
  with the same kind; its message now names the date as `Y-n-j`, as before.
- `Calendar::toSerial`, the Gregorian validity helpers and `Names::toNepaliDigits` had no caller
  left and are removed. They were internal.
- `CalendarTable.php` grows from about 5 KB to about 27 KB of source.
- `docs/benchmarks.md` keeps its preliminary figures until the quiet rerun replaces them.
