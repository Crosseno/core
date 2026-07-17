# Repository guidance

- Keep this package language-independent and free of runtime dependencies beyond PHP.
- Do not add tokenization, normalization, generation, clue, persistence, CMS, or randomization logic.
- Preserve exact Unicode code-point sequences at core boundaries.
- Keep public domain objects immutable; isolate mutation in builders.
- Require caller-supplied `ResourceLimits` before allocating from external data.
- Keep snapshots deterministic and reject unknown schema versions and fields.
- Run `composer check` before handing off a change.
