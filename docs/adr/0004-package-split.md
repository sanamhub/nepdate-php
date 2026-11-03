# ADR-0004: two packages in one repository

- Status: accepted
- Date: 2026-11-02

## Context

The hub's ADR-0004 decision 2 keeps a port's core and patro packages in one repository and releases
them on one tag. `D-13` keeps tithi, holiday and event data out of the core package, so
`sanamhub/nepdate-patro` must be a separate Composer package that requires `sanamhub/nepdate`.

Composer has no native monorepo publishing: Packagist reads one repository per package and derives
the version from the tag it finds there. The two options the ecosystem uses are a subtree split to a
read-only mirror repository, or a `path` repository in every consumer, which only works locally.

## Decision

1. **Core and patro live in one repository.** `src/` is `NepDate`, `packages/nepdate-patro/src/` is
   `NepDate\Patro`. One `CHANGELOG.md` and one version number cover both.
2. **Publishing uses a subtree split mirror.** The release workflow splits
   `packages/nepdate-patro` to its own branch and force-pushes it to `sanamhub/nepdate-patro` with the
   same tag, so Packagist serves `sanamhub/nepdate-patro` from that mirror and `sanamhub/nepdate` from
   this repository. The mirror is read-only: nobody commits to it, and the split runs on every tag
   (`release.yml`).
3. **Development resolves the core package through a `path` repository** in the root
   `composer.json`. A `composer install` at the root therefore provides both packages to the test
   suite, and `packages/nepdate-patro` needs no install of its own while developing.
4. **The patro package's `require` pins the core to `^<version>`**, the same version the core
   declares, so a consumer cannot mix a patro release with an older core. `scripts/version.php` and
   `scripts/release-check.php` enforce it, and CI runs both.
5. **Both manifests declare `version`.** This is a Composer anti-pattern in general, but it is what
   the family's lockstep line needs: a manifest that says `0.2.0-rc.1` is the single place a reader,
   a script and the split can check. `composer validate` (not `--strict`) is the gate, because the
   strict form warns about the explicit version.

## Consequences

- A user installs `sanamhub/nepdate` and, if they need tithi or holidays, also
  `sanamhub/nepdate-patro`. The patro package alone pulls the core in.
- The split mirror must exist before the first patro release; the owner steps are in
  `docs/runbooks/release.md`.
- A change that touches both packages is one pull request and one tag.
- `composer ci` runs from the root only; a standalone `composer install` inside
  `packages/nepdate-patro` resolves the core from Packagist, which is the consumer's path and is
  exercised by `verify-published.yml` rather than by the development loop.
