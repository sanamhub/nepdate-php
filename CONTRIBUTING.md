# Contributing

Thanks for helping. This repository holds the PHP `nepdate` library (Composer package
`sanamhub/nepdate`, namespace `NepDate`) and the `nepdate-patro` package (`sanamhub/nepdate-patro`,
namespace `NepDate\Patro`).

This project follows the [Code of Conduct](CODE_OF_CONDUCT.md). Report security problems as
described in [SECURITY.md](SECURITY.md), not in a public issue.

## Before you start

- Open an issue before a new feature or an API change, so we can agree on it first.
- Data and spec problems (a wrong date, holiday or tithi) belong in the hub,
  [`sanamhub/nepdate`](https://github.com/sanamhub/nepdate). `shared/` here is a read-only copy.

## Setup

- PHP 8.3 or newer and [Composer](https://getcomposer.org/) are the only tools you need.
  `composer install` creates the environment from the committed `composer.lock`.
- `packages/nepdate-patro` has its own `composer.json`; run `composer install` there too (it
  resolves the core package from the local `path` repository during development).

## Making a change

1. Run `composer ci` before you open a pull request. It must pass.
2. One change per pull request.
3. Add tests for every behaviour change; prefer a vector row in `shared/spec/vectors` over a
   hand-written case when one already covers it.
4. Add a changelog line for anything a user would notice.
5. Write an ADR in `docs/adr/` for a significant decision.
6. Use [Conventional Commits](https://www.conventionalcommits.org/), for example
   `feat(nepdate): add serial conversion`.
7. Commit messages and docs: imperative summary, lower case, no trailing period; the body says
   why, not what. Docs are plain prose without em dashes and without emoji.

## Documentation

`README.md` and `docs/API.md` are executable. A line of the form

```php
$date->isoformat(); // => '2081-04-15'
```

is run and checked by `composer docs:check`, and `composer ci` runs it, so a doc example cannot
drift from the code. A line ending `// => throws NepDateException(InvalidDay)` asserts the error
kind instead. Write `not measured` rather than guessing a benchmark figure.

## Benchmarks

`php bench/run.php` is the harness of `shared/spec/BENCHMARK.md`: B1 to B8 over the shared 64 dates
and the two competitor rows of `docs/competitors.md`. `bench/CoreBench.php` is the same operations
under PHPBench, for a regression check. Change a number in `docs/benchmarks.md` only together with a
run, and say on which machine it was measured.


## Generated files

`src/Internal/CalendarTable.php` and every file under `packages/nepdate-patro/src/Internal/` are
generated from `shared/data/`. Change `scripts/generate-tables.php` and run
`composer codegen`. Don't edit the generated files by hand; CI runs `composer codegen:check` and
fails on drift.
