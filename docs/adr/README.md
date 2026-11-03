# Architecture decision records

One file per decision that a reader of the code would otherwise have to ask about. Each record
names its status, its date, the problem, the decision and what follows from it.

| ADR | Title | Status |
|---|---|---|
| [0001](0001-language-and-toolchain.md) | The language, the package names and the toolchain | accepted |
| [0002](0002-immutable-value-types-and-errors.md) | Immutable value types and exceptions | accepted |
| [0003](0003-generated-tables.md) | Generated calendar and patro tables | accepted |
| [0004](0004-package-split.md) | Two packages in one repository | accepted |
| [0005](0005-serialization.md) | Serialization | accepted |
| [0006](0006-tests-and-gates.md) | Tests, gates, coverage and mutation | accepted |
| [0007](0007-releases-and-publishing.md) | Releases and publishing | accepted |

Behaviour that a decision of the shared spec already covers is not an ADR here; it is a `D-xx` row
of `shared/spec/PARITY.md`, and the code names that row where it applies.
