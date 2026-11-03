## What and why

<!-- The change and the reason for it. One change per pull request. -->

## Checks

- [ ] `composer ci` passes (codegen drift, code style, PHPStan, documentation examples, tests)
- [ ] A behaviour change has a test, preferably a row in `shared/spec/vectors`
- [ ] A user-visible change has a changelog line under `Unreleased`
- [ ] A significant decision is written up as an ADR in `docs/adr/`

## Notes for the reviewer

<!-- Anything a reader would otherwise have to rediscover: a `D-xx` row, a spec paragraph, a
     vector row that holds the port's value rather than the C# one. -->
