# ADR-0007: releases and publishing

- Status: accepted
- Date: 2026-11-02

## Context

The two packages release together on one tag (hub ADR-0004 decision 2) and join the family's
lockstep line, whose first release is `0.2.0-rc.1`. Packagist has no upload step: it reads the
GitHub repository, keeps a webhook, and derives the version from the tag. That removes the problem
most ecosystems have (a publish token) and adds another: the second package lives in a
subdirectory, which Packagist cannot read (ADR-0004).

## Decision

1. **One tag `vX.Y.Z` releases both packages.** The manifests, the changelog and the tag must agree;
   `scripts/version.php --check-tag` and `scripts/release-check.php` prove it before anything else
   runs in `release.yml`.
2. **The release workflow never uploads.** It runs every gate, builds the source archives with
   `git archive`, attests them with GitHub build provenance, and creates the GitHub release with the
   changelog's `Unreleased` section as its notes. Packagist picks the tag up through the webhook.
3. **The patro package reaches Packagist through the split mirror** pushed by the `split` job
   (ADR-0004), which is the one step that needs a secret (`MIRROR_TOKEN`) and the `production`
   environment.
4. **The `production` environment holds the tag rule** `v*` and the secret, so a release needs an
   approval from the owner and cannot be triggered by a branch push.
5. **A manual dispatch is a dry run.** It runs the gates and the packaging and creates nothing.
6. **`verify-published.yml` installs the published version** in a throw-away consumer project on
   Linux, macOS and Windows, runs `scripts/published-smoke.php` against it, and runs daily as a
   metadata-only check of the two Packagist listings. A scheduled failure opens one issue labelled
   `published-check`.
7. **There is no `version` file and no tag script**: the version lives in the two manifests, which is
   where Packagist, Composer and a reader all look.

## Consequences

- The first release needs owner steps that no workflow can do: create the repository, create the
  mirror, enable the Packagist webhook, create the environment and the secret. They are in
  `docs/runbooks/release.md`, in order.
- A tag on a commit whose manifest disagrees fails before building anything.
- Rolling back means deleting a tag and, for the mirror, force-pushing the split again; the runbook
  says how.
