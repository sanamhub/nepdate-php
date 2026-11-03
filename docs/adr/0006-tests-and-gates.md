# ADR-0006: tests, gates, coverage and mutation

- Status: accepted
- Date: 2026-11-02

## Context

Every behavioural decision in this port traces to `shared/spec/*.md` and to the golden vectors under
`shared/spec/vectors/`. The hub's vectors are the same files every other port reads, so the PHP
suite is the port's only proof that it agrees with the family. `D-04` and `D-16` mean a handful of
vector rows hold a port value that differs from the C# recording, and `D-05` means the strict parser
accepts one input C# rejects.

## Decision

1. **Every vector row is a test.** `tests/Vectors/` has one suite per vector file, driven by a
   data provider, with the `#` preamble skipped and the `ERR`/`ERR:Kind`/`none`/`;`-list notations
   understood (`tests/Support/Vectors.php`). A row whose note is a `D` number holds the port's value,
   not the C# error.
2. **A deliberate deviation is skipped by rule, not by hand.** The `MMM` rows for Ashad and Ashoj
   (`D-04`) are skipped in `FormatPatternTest` by month and pattern, and the deviation is asserted in
   its own test, so a regression cannot hide in a skip list.
3. **`composer ci` is the gate**: `codegen:check`, `cs` (php-cs-fixer), `stan` (PHPStan level
   `max`), `docs:check` and `test`. It must pass on every pull request and on PHP 8.3, 8.4 and 8.5.
4. **Coverage is measured and gated at 90% of statements** (`scripts/coverage-check.php`, run by the
   `coverage` job of `ci.yml` with PCOV). PHPUnit has no threshold option, so the gate is a script
   rather than another dependency. The current figure is 97%.
5. **Mutation testing runs weekly, never on a pull request.** `infection.json5` mutates both `src/`
   trees except the generated tables; `mutation.yml` runs it with PCOV and keeps the report. A full
   run costs tens of minutes because every mutant runs the tests that cover it.
6. **Every documentation example runs.** `scripts/docs-check.php` executes each PHP block in
   `README.md` and `docs/API.md` and checks every `// => value` line, so a doc example cannot drift
   from the code.
7. **The unit suite covers what no vector can**: the clock methods with an explicit zone, the try*
   forms, batch order, the enum naming tables and the fiscal-year labels.

## Consequences

- The suite is 23,307 tests and 324,634 assertions over 29,215 vector rows. The two large vector files (add-days, add-months)
  take most of the 37 seconds.
- `phpunit.xml` raises `memory_limit` to 512 MB because a few hundred thousand assertions and the
  109,212-date round trip do not fit in 128 MB.
- A new vector file means a new suite in `tests/Vectors/`, not a new framework.
