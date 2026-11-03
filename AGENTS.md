# AGENTS.md

Instructions for an agent (human or AI) working in this repository.

## What this repository is

The PHP port of NepDate: Bikram Sambat (Nepali) dates. Two Composer packages from one repository:

- `sanamhub/nepdate` (namespace `NepDate`) at the repository root, `src/`.
- `sanamhub/nepdate-patro` (namespace `NepDate\Patro`) at `packages/nepdate-patro/`. It depends
  on the core package; the core package never depends on it (`docs/adr/0004-package-split.md`).

`shared/` is a read-only copy of the hub's `data/` and `spec/`, synced by
`shared_sync.py` in the hub, never edited here. Every behavioural decision traces to
`shared/spec/*.md` and the golden vectors in `shared/spec/vectors/`. A spec and a vector
disagreeing, or a decision not covered by `shared/spec/API.md` or `docs/adr/`, is a reason to stop
and ask, not to guess.

## Setup

PHP 8.3 or newer and Composer. `composer install` at the repository root is enough: the root
`composer.json` has a `path` repository pointing at `packages/nepdate-patro`, so both packages and
both test suites come from one install. A standalone install inside `packages/nepdate-patro` is the
consumer's path, not the development one.

## Everyday commands

| Command | What |
|---|---|
| `composer ci` | codegen drift check, code style, PHPStan, the documentation examples and PHPUnit: run this before every commit |
| `composer codegen` | regenerate `src/Internal/CalendarTable.php` and the patro tables from `shared/data/` |
| `composer test` | PHPUnit only |
| `composer stan` | PHPStan only, level `max` |
| `composer cs:fix` | apply the code style fixes `composer cs` would otherwise only report |
| `composer docs:check` | run every PHP example in `README.md` and `docs/API.md` |

## Rules

- Generated files say `DO NOT EDIT` at the top. Change the generator in `scripts/`, not the file.
- The core package's public surface is exactly `shared/spec/API.md`'s PHP column; nothing more.
- Every intentional deviation from the C# reference names a `D-xx` row of
  `shared/spec/PARITY.md` in a comment; nothing else changes the golden-vector behaviour.
- No AI attribution, task identifiers or internal checkpoint references in commits, code
  comments, or documentation. This is a normal open-source repository.
- Conventional Commits; see `CONTRIBUTING.md`.
- The two-string form of `strtr()` maps bytes and corrupts UTF-8. Use the array form, always.
