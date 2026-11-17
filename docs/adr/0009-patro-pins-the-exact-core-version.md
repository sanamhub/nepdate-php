# ADR-0009: the patro requires exactly the same core version

Status: accepted. Amends ADR-0004 decision 4 (the `^<version>` constraint).

## Context

`sanamhub/nepdate` and `sanamhub/nepdate-patro` are released together under one version, and the
patro reads internals of the core that are not part of the public API. ADR-0004 decision 4 used the
constraint `^<version>`, which accepts any later 1.x core. A newer core could then run against an
older patro, a pair that was never tested. The other SDKs pin their patro package to the exact core
version.

## Decision

- `packages/nepdate-patro/composer.json` requires `"sanamhub/nepdate": "<version>"`, the exact
  version the core declares, for example `1.0.0-rc.1`.
- `scripts/version.php` fails when the constraint differs from the core version. `ci.yml` runs it on
  every push and pull request, and `release.yml` runs it again, with `scripts/release-check.php`,
  before a tag publishes.

## Consequences

- A version bump changes both `version` fields and the patro constraint in one commit.
- An app that upgrades `sanamhub/nepdate` must upgrade `sanamhub/nepdate-patro` to the same
  version. Composer reports the conflict.
