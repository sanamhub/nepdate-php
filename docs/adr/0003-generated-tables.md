# ADR-0003: generated calendar and patro tables

- Status: accepted
- Date: 2026-11-02

## Context

`shared/data/` holds the BS month lengths and the patro JSON. `AGENTS.md` says the build never reads
CSV or JSON at runtime unless that is the language's standard, and `ALGORITHM.md` §1-2 fixes the
shape of the calendar table. A PHP library that opened and parsed a data file on every request would
be slower than the C# reference and would fail on a read-only deployment. The patro data is larger:
2793 day records, 754 distinct names.

## Decision

1. **Both tables are generated into PHP source and committed:**

   | Generator | Output | Layout |
   |---|---|---|
   | `scripts/generate-tables.php` | `src/Internal/CalendarTable.php` | 299 packed month-length integers (2 bits per month) and a cumulative year-start array |
   | `packages/nepdate-patro/scripts/generate-patro-tables.php` | `packages/nepdate-patro/src/Internal/PatroTable.php` | tithi index per day, sorted holiday serials, a CSR event table and a deduplicated name pool |

2. **Nothing under `shared/` is read at runtime.** The generators read it, CI and the release build
   run them with `--check`, and a drift fails the build (`composer codegen:check`).
3. **The patro generator validates against `shared/spec/vectors/patro-summary.json`** and refuses to
   write when the counts disagree, so a data update cannot silently change a count. It never pins a
   count of its own (`PATRO.md` §3).
4. **Serials, not dates, are stored** in the patro table: one `int` per holiday and one index per
   day. A lookup is a subtraction and an array read for tithi, and a binary search of at most 11
   comparisons for holidays and events (`ALGORITHM.md` §4).
5. **`EPOCH_UNIX_DAYS` and `EPOCH_WEEKDAY` live in the generated file**, so the calendar has one
   source of truth and the Gregorian conversion needs no `DateTime` at all.

## Consequences

- The package has no runtime data dependency: `vendor/` plus `src/` is enough, and a read-only
  filesystem works.
- A month-length change is a generated diff, reviewed like any other code, and the golden vectors
  catch a wrong table immediately.
- `PatroTable.php` is 123 KB of source. It is excluded from PHPStan's path list and from
  `php-cs-fixer` by filename, and its arrays are used as plain literals, so the constant folding is
  done by the opcode compiler.
