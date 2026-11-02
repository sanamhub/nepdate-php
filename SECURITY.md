# Security policy

## Supported versions

The latest release of each package: `sanamhub/nepdate` (namespace `NepDate`) and
`sanamhub/nepdate-patro` (namespace `NepDate\Patro`). Older versions get no fixes.

## Reporting a vulnerability

Report privately through
[GitHub security advisories](https://github.com/sanamhub/nepdate-php/security/advisories/new).
Don't open a public issue. Expect a first reply within a week.

Say which package and version are affected and how to reproduce it. A wrong date, holiday or tithi
isn't a security problem: open a bug report, or a data error in the hub
([`sanamhub/nepdate`](https://github.com/sanamhub/nepdate)).

## Static analysis coverage

[CodeQL](https://codeql.github.com/) has no PHP query pack, so `codeql.yml` in this repository
only scans the GitHub Actions workflows themselves (the `actions` language), not the PHP source.
PHP static analysis instead comes from PHPStan at its strictest level (`phpstan.neon`, level
`max`), run on every pull request, plus [OpenSSF Scorecard](https://securityscorecards.dev/) and
GitHub's Dependency Review.
