# Data update runbook

`shared/` is a copy of the hub's `data/` and `spec/`. It is never edited here: a data change is a hub
pull request, and this repository picks it up.

## How the copy arrives

`shared-sync.yml` runs weekly (Tuesday 03:47 UTC) and by hand. It replaces `shared/data` and
`shared/spec` with the hub's `main`, records the new commit in `shared/VERSION`, runs
`composer codegen` and opens one pull request on `bot/shared-sync` with the table diff and the hub
commits since the last sync. `ci.yml` has a `workflow_dispatch` trigger so that the PR gets checks
even when it was opened with `GITHUB_TOKEN`.

The local equivalent, from the hub checkout:

```sh
python ../nepdate/tools/shared_sync.py .
php scripts/generate-tables.php
php packages/nepdate-patro/scripts/generate-patro-tables.php
```

## Checking the sync by hand

```sh
python ../nepdate/tools/shared_sync.py . --check
php scripts/generate-tables.php --check
php packages/nepdate-patro/scripts/generate-patro-tables.php --check
```

The reusable `shared-check` job runs the same comparison against the hub commit in `shared/VERSION`.

## What a data update can change

| Data | Effect |
|---|---|
| A BS month length | `src/Internal/CalendarTable.php` changes; the `dates`, `add-days`, `add-months` and `month-boundaries` vectors fail until the table is right |
| A patro day | `packages/nepdate-patro/src/Internal/PatroTable.php` changes; the generator fails if the counts in `patro-summary.json` disagree |
| A new vector file | A new suite in `tests/Vectors/`; the file is in the spec, so the suite is the port's obligation |
| A spec paragraph | Read it against the code, and add a `D-xx` reference or an ADR when the code deviates |

## After merging a sync

Release a patch version (`docs/runbooks/release.md`). The generated tables are committed, so the
release is what makes a data fix reach a user.
