# ADR-0005: serialization

- Status: accepted
- Date: 2026-11-02

## Context

The C# library has four serialization adapters: a `TypeConverter`, a `System.Text.Json` converter, a
Newtonsoft converter and an XML serializer, each with a string and an object form. `D-11` and
decision `O-07` cut that down to the language's native hook and the canonical text `YYYY-MM-DD`. The
`YYYYMMDD` integer stays part of the contract (`NC09`), and a JSON `null` must not become the C#
zero-value date, because there is no zero value here (`D-01`).

## Decision

1. **`NepaliDate implements JsonSerializable`**, and `jsonSerialize()` returns the canonical text
   `2081-04-15`. `json_encode($date)` therefore writes `"2081-04-15"`.
2. **`NepaliDate::fromJson(string $json): self` reads that form.** It decodes the JSON text first,
   so the argument is the JSON document (`"\"2081-04-15\""`), not the raw date; anything that is not
   a JSON string, including `null`, is an `ErrorKind::InvalidCharacter` failure.
3. **`__toString()` stays the default display form** `2081/04/15` (`NF01`), not the serialized form.
   A date used as a string key and a date written to JSON therefore differ by design, and the docs
   say so.
4. **The `YYYYMMDD` integer is `toYmd()` and `fromYmd()`** (`NC09`), with no converter type.
5. **No object form, no XML, no adapter registration.** `NC11` to `NC16` are not ported.

## Consequences

- A framework's JSON encoder needs no registration: `json_encode` and `json_decode` already work.
- A consumer that needs the object form (`{"Year": 2081, ...}`) writes it from `toArray()`.
- Round-tripping is textual, so a date survives any transport that carries strings, and the
  `serialization.tsv` vectors cover the read, write and round-trip rows.
