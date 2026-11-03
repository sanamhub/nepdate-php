# ADR-0002: immutable value types and exceptions

- Status: accepted
- Date: 2026-11-02

## Context

The C# `NepaliDate` is a `readonly struct` that can hold an invalid date: the default value has
year, month and day zero, and `IsDefault` exists to detect it (`D-01`). The conversion and parse
paths signal failure in three different ways in C#: a default value, an exception, and a
`TryParse`-style boolean with an `out` parameter.

PHP's own date type, `DateTimeImmutable`, is the model users expect, and `D-01` allows a port to
make invalid dates unrepresentable. `D-05`, `D-06` and `D-07` replace the nullable and out
parameter forms with explicit outcomes.

## Decision

1. **`NepaliDate`, `NepaliDateRange`, `FiscalYear`, `DateDiff`, `Tithi`, `NepEvent` and
   `CalendarInfo` are immutable.** Their state is `private readonly` or `public readonly`, and every
   operation returns a new value. There is no `IsDefault`, no nullable date and no magic setter.
2. **Construction validates, so every instance is a real date.** The year is checked first, then the
   month, then the day, in that order, so the first error a user sees is the meaningful one.
3. **Failures are `NepDateException`, which carries a `kind(): ErrorKind`.** `ErrorKind` has the
   parse kinds of `PARSING.md` §4 plus the validation kinds, so a caller can branch on the kind
   instead of parsing a message. The class extends `RuntimeException`, because a date failure is not
   a programming error the caller can avoid by type.
4. **Every throwing entry point has a `try*` form that returns `null`**: `tryParse`,
   `tryParseLenient`, and `intersect` for the empty-intersection case. This is the PHP reading of
   `NP03`, `NP08` and `NR19`; there is no `out` parameter in PHP.
5. **A range refuses to be empty.** `NepaliDateRange::of()` throws when the start is after the end,
   and `intersect` returns `null` rather than an empty range (`D-01`, `D-16`).

## Consequences

- A `NepaliDate` parameter needs no validation in the callee: if the value exists, the date is real.
- A caller who wants the boolean form writes `NepaliDate::tryParse($text) !== null`; a caller who
  wants the reason catches the exception and reads `kind()`.
- `NepaliDateRange::count()` is always at least 1, so `Length == 0` and `IsEmpty` have no PHP
  equivalent (`NR05` is not ported).
