# ADR-0001: the language, the package names and the toolchain

- Status: accepted
- Date: 2026-11-02

## Context

The NepDate family ports the C# reference library to one language per repository `D`-row
(`shared/spec/PORT-PLAN.md`, hub ADR-0004). PHP is the language most Nepali applications are
written in, and the port must work in Laravel, Symfony and plain PHP alike, on PHP 8.3 or newer,
with no extension and no FFI. The C# reference serializes dates through `DateTime`, `TypeConverter`
and two JSON adapters; `D-11` keeps only the language's own string hook.

## Decision

1. **PHP 8.3 or newer**, pure PHP. `composer.json` says `"php": "^8.3"`. 8.2 leaves security
   support on 2026-12-31, so 8.3 is the oldest version worth supporting.
2. **No extension, no FFI.** The library calls no C function and needs no `mbstring`: the two
   multi-byte jobs, splitting a UTF-8 string into characters and mapping Devanagari digits, are
   done with `preg_split('//u', ...)` and `strtr()` with an array of pairs. `mbstring` is installed
   in development only because PHPUnit requires it.
3. **The Composer package is `sanamhub/nepdate`, the namespace is `NepDate`.** The patro package is
   `sanamhub/nepdate-patro` with the namespace `NepDate\Patro` (ADR-0004). PSR-4 maps `NepDate\` to
   `src/`.
4. **No framework coupling.** Nothing here references Laravel, Symfony or any container. A service
   provider is the framework's business, and the whole API is static constructors plus immutable
   value objects, so no wiring is needed.

## Consequences

- Every public item is in the `NepDate` or `NepDate\Patro` namespace, and `composer.json`'s
  autoload is the only registration step a user needs.
- The Devanagari handling must stay byte-exact. Two bugs in the first draft came from the two-string
  form of `strtr()`, which maps bytes and corrupts UTF-8; the repository now forbids it in comments
  and the vector suites catch it.
- PHP 8.3, 8.4 and 8.5 are all in CI, on Linux, macOS and Windows (`ci.yml`).
