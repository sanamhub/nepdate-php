# Benchmarks

**Preliminary.** These numbers were measured on the reference machine while other work was running
on it, so they are a first pass, not the published figures. The quiet rerun recorded in
`shared/spec/BENCHMARK.md` replaces this file's tables.

## Machine and toolchain

| Item | Value |
|---|---|
| CPU | 12th Gen Intel Core i5-12500H (4 performance + 8 efficiency cores, 16 threads, base 3.1 GHz) |
| RAM | 31.7 GB |
| OS | Windows 11 Home 10.0.26300 |
| PHP | 8.3.36 NTS, VS16 x64, OPcache off |
| Harness | `bench/run.php` (no framework), `bench/CoreBench.php` under PHPBench 1.7.0 |
| Command | `php bench/run.php --iterations=20 --json=bench/results/preliminary-php-8.3.36.json` |

The machine was not quiet: the figures below are preliminary by design (`BENCHMARK.md` §5 and the
port's plan). Affinity was not pinned, so a run can move between performance and efficiency cores;
the standard deviation column shows how uneven that is.

## This port

64 inputs per iteration, 20 measured iterations after a warm-up pass; `ns/op` is the mean per single
operation. Every result is consumed by the harness, so nothing is optimised away.

| ID | Operation | Unit | ns/op | sd |
|---|---|---|---:|---:|
| B1 | BS to AD: `fromBs`, then `toDateTime` | core | 1456 | 157 |
| B2 | AD to BS: `fromGregorian` | core | 2427 | 103 |
| B3 | add 1000 days | core | 2235 | 56 |
| B4 | add 13 months, `MonthOverflow::Clamp` | core | 1793 | 119 |
| B5 | long format, Nepali, with weekday | core | 1067 | 22 |
| B6 | strict parse of `YYYY/MM/DD` | core | 2525 | 52 |
| B7 | patro info (tithi, holiday, events) | patro | 2105 | 33 |
| B8 | patro month grid (every day of the month) | patro | 115490 | 2069 |

`bytes/op` is **not measured**. PHP's allocator reuses freed memory, so a per-operation byte count
would be an artefact rather than a measurement; `BENCHMARK.md` §4 allows the omission for the
languages where no reliable method exists, as it already does for Python and TypeScript.

What the table says about cost: B1 and B2 are dominated by `DateTimeImmutable` (B1) and by the
`DateTimeInterface` read plus the civil-date conversion (B2). B5 and B6, which touch no `DateTime`,
are the cheapest. B8 is 32 to 33 lookups per operation by definition, so its figure divided by the
month length is the same as B7's: 3.5 µs against 2.1 µs per day.

## Competitors

Selected in [`docs/competitors.md`](competitors.md) by the `BENCHMARK.md` §7 rule: two libraries pass
the maturity bar. Both were measured through their own public API, installed in
`bench/competitors/` so they never touch the library's dependency tree.

| Library | Operation | ns/op | Inputs answered | Agreement |
|---|---|---:|---:|---|
| `anuzpandey/laravel-nepali-date` 3.3.0 | BS to AD | 1,470,822 | 62 of 64 | 60 of 64 |
| `anuzpandey/laravel-nepali-date` 3.3.0 | AD to BS (text) | 1,676,688 | 62 of 64 | 60 of 64 |
| `ernilambar/nepali-date` 1.0.9 | BS to AD | 5,773,987 | 62 of 64 | 60 of 64 |
| `ernilambar/nepali-date` 1.0.9 | AD to BS (text) | 4,913,110 | 62 of 64 | 60 of 64 |
| **NepDate for PHP** | BS to AD (B1) | 1,456 | 64 of 64 | 64 of 64 |
| **NepDate for PHP** | AD to BS (B2) | 2,427 | 64 of 64 | 64 of 64 |

The comparison is not like for like and the table does not rank on time alone: both competitors
format a string where B1 and B2 return a value, both accept fewer of the 64 dates, and their
disagreements are in [`docs/competitors.md`](competitors.md). What it does show is the order of
magnitude: this port answers in microseconds, the two most-used PHP packages in milliseconds.

## The C# reference

`BENCHMARK.md` §5 puts the C# column beside every port. It comes from the hub's
`docs/benchmarks/dotnet.md` (.NET 10 column), measured with BenchmarkDotNet on the same machine. It
is not copied here because this run is preliminary; the quiet rerun brings the column in with the
hub's figure at that time.

## Reproducing

```sh
composer install
composer install --working-dir=bench/competitors   # the two competitors, kept out of the lock
php bench/run.php --iterations=20
vendor/bin/phpbench run bench/CoreBench.php --config=bench/phpbench.json
```

`bench/run.php` writes the raw figures with `--json=...`; one preliminary run is kept at
`bench/results/preliminary-php-8.3.36.json`. `bench/CoreBench.php` is the same eight operations
under PHPBench, for a regression check rather than for the table: one iteration is one pass over the
64 inputs, so its per-iteration time is 64 × the per-operation figure above.
