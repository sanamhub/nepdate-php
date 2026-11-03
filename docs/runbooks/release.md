# Release runbook

The owner steps for the first release, in order, then the steps every release repeats. `release.yml`
does the checks and the packaging; this file is what a human has to do around it.

## Once, before the first release

1. **Create the repository.** `sanamhub/nepdate-php`, public, and push `main`:

   ```sh
   git remote add origin git@github.com:sanamhub/nepdate-php.git
   git push -u origin main
   ```

2. **Create the patro mirror.** `sanamhub/nepdate-patro`, public, empty. It is read-only in practice:
   `release.yml` force-pushes the subtree split to it on every tag (ADR-0004).
3. **Protect `main`.** Require a pull request, require the `ci` checks, require branches to be up to
   date, and disable force pushes.
4. **Create the `production` environment** with a deployment branch and tag rule of `v*`, and require
   a reviewer. Add the secret it needs:

   | Secret | What | Why |
   |---|---|---|
   | `MIRROR_TOKEN` | A fine-grained personal access token with `contents: write` on `sanamhub/nepdate-patro` | The split push to the mirror |

   `SYNC_APP_PRIVATE_KEY` and the `SYNC_APP_ID` variable belong to the hub's `shared-sync.yml`
   (optional; without them the PR is opened with `GITHUB_TOKEN`).
5. **Register both packages on Packagist.** `sanamhub/nepdate` from this repository, and
   `sanamhub/nepdate-patro` from the mirror. Enable the GitHub webhook Packagist offers, so a tag
   updates both. Create the `published-check` label used by `verify-published.yml`.
6. **Publish a GitHub release for the tag** only after Packagist serves the version; the workflow
   does it, but the first one needs both packages listed, so confirm the listings first.
7. **Wait for the hub.** `shared-check` compares `shared/` with the hub commit in `shared/VERSION`
   and checks this port's version against the hub's `spec/versions.json`. The `php` entry and the
   `0.2` line land with the hub branch for this port; until that is merged the `shared-check` job of
   `ci.yml` is the one red check, and no tag should be cut before it is green.

## Every release

1. Land the changes on `main` with a changelog line under `Unreleased`.
2. Bump the version in both manifests and in the patro package's constraint:
   - `composer.json` → `version`
   - `packages/nepdate-patro/composer.json` → `version` and `require["sanamhub/nepdate"]`

   `scripts/version.php` and `scripts/release-check.php` check all three.
3. Run `composer ci` locally.
4. Tag and push:

   ```sh
   git tag -a v0.2.0-rc.1 -m "v0.2.0-rc.1"
   git push origin v0.2.0-rc.1
   ```

5. Approve the `production` environment when the workflow asks.
6. Watch the run: `pack` (gates and archives), `split` (mirror push), `release` (attestation and
   GitHub release), `verify` (installs the published version). Packagist may take a minute to serve
   the new tag; `verify` waits for the listing rather than failing at once.
7. After the release, move the changelog's `Unreleased` items under the released version in a
   follow-up commit.

## If something is wrong

- **A tag on the wrong commit.** Delete it (`git push origin :v0.2.0-rc.1` and `git tag -d`), fix,
  tag again. Packagist keeps the old entry for a deleted tag; a new patch version is cleaner once a
  version has been served.
- **The mirror push failed.** The `split` job force-pushes, so re-running it is safe. Check the token
  has `contents: write` on the mirror.
- **A published version is broken.** Release a patch version. Yanking a Packagist version is not a
  thing; `verify-published.yml` will keep reporting the broken one, so the issue it opens is the
  record.
- **`shared/` is out of date.** Never edit it by hand: merge the `shared-sync` pull request, which
  runs the generators, then release a patch.
