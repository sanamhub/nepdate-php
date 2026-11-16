# Benchmarks

B1 to B8 of `shared/spec/BENCHMARK.md` for this port, the two PHP libraries that
[`docs/competitors.md`](competitors.md) selects, and the C# NepDate baseline from the hub. The run
is quiet: the machine was idle, the process was pinned to logical CPU 2 and the load was sampled
before and during the run. It replaces the earlier preliminary figures.

## Machine and toolchain

| Item | Value |
|---|---|
| CPU | 12th Gen Intel Core i5-12500H (4 performance + 8 efficiency cores, 16 threads, base 3.1 GHz) |
| RAM | 31.7 GB |
| OS | Windows 11 Home 10.0.26300 |
| PHP | 8.3.36 NTS, VS16 x64, OPcache off |
| Harness | `bench/run.php` (no framework), `bench/CoreBench.php` under PHPBench 1.7.0 |
| Affinity | logical CPU 2 (mask `0x4`), set with `start /affinity 4 /wait /b` |
| Date of run | 2026-11-16 |
| Idle gate before the run | 4.5 percent average, 9.4 peak (30 one-second samples) |
| Load during the run | 5.1 percent average, 13.6 peak over 102 one-second samples; 4.8 and 9.0 on the CPUs other than CPU 2 |
| Command | `start "" /affinity 4 /wait /b php bench/run.php --iterations=20 --json=bench/results/2026-11-16-php-8.3.36.json` |

The machine was idle: no other build, test suite or benchmark ran during the run, and the load
during the run includes the benchmark's own pinned core and the sampler. The raw figures are in
[`bench/results/2026-11-16-php-8.3.36.json`](../bench/results/2026-11-16-php-8.3.36.json), the table
the harness printed is `2026-11-16-php-8.3.36.txt` beside it, and the load samples are
`2026-11-16-cpu-samples.txt`.

## This port

64 inputs per iteration, 20 measured iterations after a warm-up pass; `ns/op` is the mean per single
operation. Every result is consumed by the harness, so nothing is optimised away.

| ID | Operation | Unit | ns/op | sd | C# NepDate ns/op |
|---|---|---|---:|---:|---:|
| B1 | BS to AD: `fromBs`, then `toDateTime` | core | 986 | 31 | 13.27 |
| B2 | AD to BS: `fromGregorian` | core | 785 | 198 | 18.70 |
| B3 | add 1000 days | core | 935 | 253 | 20.06 |
| B4 | add 13 months, `MonthOverflow::Clamp` | core | 726 | 75 | 19.30 |
| B5 | long format, Nepali, with weekday | core | 554 | 4 | 139.04 |
| B6 | strict parse of `YYYY/MM/DD` | core | 963 | 9 | 21.13 |
| B7 | patro info (tithi, holiday, events) | patro | 323 | 5 | 48.63 |
| B8 | patro month grid (every day of the month) | patro | 20,888 | 3,224 | 3,274.50 |

The C# column is the .NET 10 column of the hub's
[`docs/benchmarks/dotnet.md`](https://github.com/sanamhub/nepdate/blob/main/docs/benchmarks/dotnet.md),
measured on the same machine on 2026-11-16 with BenchmarkDotNet. BENCHMARK section 6 gives PHP no
fixed target: "C, Dart, PHP, Zig: no fixed target: the numbers are reported beside C# NepDate and
the selected competitors". PHP is an interpreter and runs this port 4 to 74 times slower than
C# on B1 to B7; the table is reported as it is.

B2, B3 and B8 vary more between iterations than the other rows (the sd column). A second run
straight afterwards, not published, gave B1 to B8 of 1,002, 547, 787, 688, 561, 1,068, 320 and
18,342 ns, so rows with a large sd can move by about 30 percent from run to run.

`bytes/op` is **not measured**. PHP's allocator reuses freed memory, so a per-operation byte count
would be an artefact rather than a measurement; `BENCHMARK.md` §4 allows the omission for the
languages where no reliable method exists, as it already does for Python and TypeScript.

What the table says about cost: B1 is dominated by `DateTimeImmutable`, and B2 by the
`DateTimeInterface` read plus the civil-date conversion. B5 and B7 are the cheapest. B8 is 32 to 33
lookups per operation by definition; its figure divided by the month length is twice B7's:
about 0.65 µs against 0.32 µs per day, because B8 also builds each day's date.

## Competitors

Selected in [`docs/competitors.md`](competitors.md) by the `BENCHMARK.md` §7 rule: two libraries pass
the maturity bar. Both were measured through their own public API, installed in
`bench/competitors/` so they never touch the library's dependency tree.

| Library | Operation | ns/op | Inputs answered | Agreement |
|---|---|---:|---:|---|
| `anuzpandey/laravel-nepali-date` 3.3.0 | BS to AD | 1,516,771 | 62 of 64 | 60 of 64 |
| `anuzpandey/laravel-nepali-date` 3.3.0 | AD to BS (text) | 1,534,718 | 62 of 64 | 60 of 64 |
| `ernilambar/nepali-date` 1.0.9 | BS to AD | 3,661,036 | 62 of 64 | 60 of 64 |
| `ernilambar/nepali-date` 1.0.9 | AD to BS (text) | 3,178,989 | 62 of 64 | 60 of 64 |
| **NepDate for PHP** | BS to AD (B1) | 986 | 64 of 64 | 64 of 64 |
| **NepDate for PHP** | AD to BS (B2) | 785 | 64 of 64 | 64 of 64 |

The comparison is not like for like and the table does not rank on time alone: both competitors
format a string where B1 and B2 return a value, both accept fewer of the 64 dates, and their
disagreements are in [`docs/competitors.md`](competitors.md). What it does show is the order of
magnitude: this port answers in microseconds, the two most-used PHP packages in milliseconds.

## Reproducing

```sh
composer install
composer install --working-dir=bench/competitors   # the two competitors, kept out of the lock
php bench/run.php --iterations=20
vendor/bin/phpbench run bench/CoreBench.php --config=bench/phpbench.json
```

`bench/run.php` writes the raw figures with `--json=...`; the run above is kept at
`bench/results/2026-11-16-php-8.3.36.json`. `bench/CoreBench.php` is the same eight operations
under PHPBench, for a regression check rather than for the table: one iteration is one pass over the
64 inputs, so its per-iteration time is 64 × the per-operation figure above.
