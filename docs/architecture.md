# Architecture

The package is the innermost dependency in the Crosseno ecosystem. `Answer` owns caller-defined grid cells and display text. Grid value objects model coordinates, symbols, closed cell states, dimensions, and placements. `Crossword` is the aggregate boundary: it materializes entries into an immutable grid and enforces bounds, blocked cells, exact crossing equality, resource ceilings, and the selected duplicate-placement policy.

Policy-dependent conventions are not aggregate invariants. Small `Validator` implementations consume an explicit `ValidationProfile` and return structured `Violation` values; `CompositeValidator` provides deterministic composition.

`DomainSnapshotSerializer` and `DomainSnapshotDeserializer` own the independent core snapshot format. They preserve exact strings, use stable field and entry ordering, reject unknown fields and versions, and validate caller-provided resource limits before constructing domain collections. Publication metadata and interoperability formats remain outside this package.

Dependencies point inward: serialization and validation depend on the domain model, while the domain model depends only on PHP. Public model values are readonly; `GridBuilder` is the sole convenience type with mutable state and emits immutable grids.
