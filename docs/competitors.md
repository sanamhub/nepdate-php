# Competitors

Which Bikram Sambat libraries a PHP user could choose instead, and what the benchmark protocol in
`shared/spec/BENCHMARK.md` §7 asks of them. Numbers read **2026-11-03** from the Packagist API
(`https://packagist.org/packages/<vendor>/<package>.json`, fields `downloads`, `versions[*].time`,
`versions[*].license`, `versions[*].require.php`) and from each repository's GitHub page for the
archived flag. Discovery: `https://packagist.org/search.json` with `nepali date`, `bikram sambat`,
`nepali calendar` and `bs date`.

## The usage rule

`BENCHMARK.md` §7 fixes a usage minimum per ecosystem, and has no Packagist row yet because PHP is a
new port. The rule applied here is provisional and is the one the hub branch for this port proposes
for the table:

> **Packagist:** total downloads, or monthly downloads, at least **10,000 total** or **500 monthly**.

It is applied to every candidate before the maturity bar, and the selections below change only if
the hub adopts a different floor.

## Candidates

| Package | Repository | License | First release | Latest release | Total | Monthly | Archived / abandoned | PHP | Passes the bar? | Selected |
|---|---|---|---|---|---|---|---|---|---|---|
| [anuzpandey/laravel-nepali-date](https://packagist.org/packages/anuzpandey/laravel-nepali-date) | [github](https://github.com/anuzpandey/laravel-nepali-date) | MIT | 1.0.0, 2023-10-24 | 3.3.0, 2026-04-28 | 33,734 | 3,267 | no / no | `^8.2\|^8.3` | yes | **yes** |
| [ernilambar/nepali-date](https://packagist.org/packages/ernilambar/nepali-date) | [github](https://github.com/ernilambar/nepali-date) | MIT | 1.0.0, 2020-06-09 | 1.0.9, 2026-04-28 | 16,837 | 1,084 | no / no | `>=5.6` | yes | **yes** |
| [pratiksh/nepalidate](https://packagist.org/packages/pratiksh/nepalidate) | [github](https://github.com/pratiksh404/nepalidate) | MIT | 1.0.0, 2021-08-05 | 2.0.3, 2026-02-08 | 7,947 | 343 | no / no | `^7.4\|^8.0` | no: usage | no |
| [milantarami/nepali-calendar](https://packagist.org/packages/milantarami/nepali-calendar) | [github](https://github.com/milantarami/nepali-calendar) | MIT | 1.0.0, 2020-02-05 | 1.4.4, 2025-04-27 | 7,468 | 256 | no / no | none declared | no: usage | no |
| [shankhadev/bsdate](https://packagist.org/packages/shankhadev/bsdate) | [github](https://github.com/shankhadevpadam/laravelbsdate) | MIT | 1.0.0, 2017-01-01 | 1.0.0, 2017-01-01 (last commit 2017-11-08) | 7,012 | 29 | no / no | `>=5.5.9` | no: recency and usage | no |
| [krishnahimself/laravel-ad-to-bs-converter](https://packagist.org/packages/krishnahimself/laravel-ad-to-bs-converter) | [github](https://github.com/krishnahimself/laravel-ad-to-bs-converter) | MIT | 1.0.0, 2020-10-04 | 1.0.1, 2021-10-17 | 3,685 | 4 | no / no | `^7.4\|^8.0` | no: recency and usage | no |
| [kmlpandey77/ndate](https://packagist.org/packages/kmlpandey77/ndate) | [github](https://github.com/ndate22/ndate) | MIT | 0.0.1, 2024-01-01 | 0.3.0, 2026-10-29 | 3,382 | 4 | no / **abandoned** | `^8.0\|\|^8.1\|\|^8.2` | no: abandoned, no 8.3, usage | no |
| [jeeven/nepali-date-converter](https://packagist.org/packages/jeeven/nepali-date-converter) | [github](https://github.com/MacAlistair1/Nepali-Date-Converter) | MIT | 1.0.0, 2025-12-03 | 1.0.3, 2026-09-22 | 2,599 | 197 | no / no | `>=8.0` | no: first release under 12 months, usage | no |
| [proshore/nepali-date-converter](https://packagist.org/packages/proshore/nepali-date-converter) | [github](https://github.com/proshore/laravel-nepali-date-converter) | MIT | 1.0.0, 2024-03-05 | 1.0.0, 2024-03-05 | 1,864 | 100 | no / no | `^8.0` | no: usage | no |
| [rohanadhikari/nepali-date](https://packagist.org/packages/rohanadhikari/nepali-date) | [github](https://github.com/rohanAdhikari1/NepalIDatePHP) | MIT | 0.0.1, 2025-10-18 | 1.0.2, 2026-07-23 | 1,576 | 70 | no / no | `^8.1` | no: usage | no |
| [fivedots/nepalicalendar](https://packagist.org/packages/fivedots/nepalicalendar) | [github](https://github.com/FiveDotsSoft/Nepali-Date-Converter) | MIT | 1.0, 2015-01-01 | 1.0, 2015-01-01 (last commit 2018-12-06) | 1,420 | 19 | no / no | `>=5.3.0` | no: recency and usage | no |
| [dipesh/nepali-date](https://packagist.org/packages/dipesh/nepali-date) | [github](https://github.com/pokhreldipesh/nepalidate) | MIT | 1.0.0, 2023-01-05 | 2.1.1, 2024-08-27 | 573 | 206 | no / no | `>=8.0` | no: usage | no |

Screened out before the table, because a total below 500 makes the provisional usage floor
unreachable: `nrngajurel/nepali-calendar` (457), `surazdott/nepali-date` (480), `devroshan/bsdate`
(398, a copy of `shankhadev/bsdate`), `krbaidik/laravel-ad-bs-converter` (198), `bhaktaraz/ghantaghar`
(188), `sabim/nepali-date` (135), `nisshan/nepali-calendar` (124), `nobelzsushank/nepali-date-converter`
(95), `nutandc/nepali-date-converter` (70), `pachabhaiya/nepali-calendar` (63), `thenineties/bsdate`
(51, a copy), `sagartimilsina/nepali-date` (44), `pusparaj/nepalicalendar` (33),
`sghimire/nepali-date-library` (28), `parad0x/nepali-date` (26), `safal/engtonepdateconverter` (24),
`laratech/bsdate` (23, a copy), `snlbaral/nepali-date` (20), `nivesh/nepali-date` (13),
`mr.incognito/date-converter` (9), `rakeshrai/laravel-nepali-date-converter` (6),
`nctpvtltd/laravel-nepali-date-converter` (5), `santhapa/nepalidateconverterbundle` (5),
`ndate22/ndate` (3), `sarojsardar/laravel-nepali-date` (2), `dev1191/laravel-bs-eloquent` (1).

Not candidates: UI wrappers rather than converters (`rohanadhikari/filament-nepali-datetime`,
`bentray/nepalidatepicker`, `dipesh/calendar`, `parad0x/event-calendar`), and one name collision that
is not a Bikram Sambat library at all (`jp3cki/yii2-bs-datetimepicker`, where "bs" is Bootstrap).

## Selection

Two libraries clear the whole bar, so the comparison is two rather than three, and the list is not
padded:

- **`anuzpandey/laravel-nepali-date` 3.3.0** (33,734 total, 3,267 monthly): the most-installed
  BS↔AD package on Packagist, MIT, active, PHP 8.2 and 8.3. It is a Laravel package: it reads its
  defaults through the `config()` helper, so the harness in `bench/competitors.php` provides a
  `config()` that returns each caller's default (`bench/framework-stubs.php`).
- **`ernilambar/nepali-date` 1.0.9** (16,837 total, 1,084 monthly): plain PHP, no framework, MIT,
  first released 2020 and last released 2026-04-28.

Close to the bar but out: `pratiksh/nepalidate` (7,947 / 343) and `milantarami/nepali-calendar`
(7,468 / 256) are mature, licensed, PHP 8.3-clean and recently released, but below both usage
thresholds; `shankhadev/bsdate` is next on lifetime downloads (7,012) with 29 a month and no commit
since 2017-11-08; `jeeven/nepali-date-converter` misses the 12-month floor by one month.

A caution on the dates: Packagist republished metadata for most of these packages in August and
September 2025, so `published-time` is not a commit date. The table uses `versions[*].time`.

## Agreement with the shared data

Measured over the 64 dates of `BENCHMARK.md` §2 (BS 2000-01-01 to 2090-12-30):
`php bench/run.php`.

| Library | Inputs answered | Dates converted correctly | What disagrees |
|---|---|---|---|
| `anuzpandey/laravel-nepali-date` 3.3.0 | 62 of 64 | 60 of 64 | Rejects BS 2000-01-01 (AD 1943-04-14) and BS 2090-11-24 (AD 2034-03-08): its documented span is AD 1944-01-01 to 2033-12-31. BS 2088-01-04 is one day early (AD 2031-04-18 instead of 2031-04-19) and BS 2089-06-10 one day early (2032-09-26 instead of 2032-09-27), both from its BS 2087 Paush length. |
| `ernilambar/nepali-date` 1.0.9 | 62 of 64 | 60 of 64 | Same two one-day differences as above (2088-01-04, 2089-06-10), and its BS→AD returns an array without year, month and day for BS 2000-01-01 and BS 2090-11-24. |

Both libraries carry BS 2062 Baishakh = 30 / Jestha = 32 where `shared/data` has 31 and 31, and BS
2087 Paush = 29 where `shared/data` has 30. The first pair keeps the same 62-day total, so it does
not move a date; the second pair moves every date from BS 2087-09-30 on by one day, which is why
2088-01-04 and 2089-06-10 disagree.

Two candidates are shown to convert incorrectly on a month boundary, and are listed here as a
warning, not as comparisons:

- **`shankhadev/bsdate`** and **`fivedots/nepalicalendar`** hard-code BS 2081 as
  `[31, 31, 32, 32, 31, 30, 30, 30, 29, 30, 30, 30]` where the shared table is
  `[31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31]`. Both convert from the epoch BS 2000-01-01 =
  AD 1943-04-14, so BS 2081-03-01 is their 63rd day of the year against the 64th on the shared
  calendar: they answer AD 2024-06-14 where `shared/spec/vectors/month-boundaries.csv` records
  2024-06-15, and they reject or overflow BS 2081-02-32. Their BS 2081-04-15 is still right, which is
  why the defect survives: it is visible only at the month boundaries of BS 2081.
