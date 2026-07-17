# ADR 0001: Keep core language- and integration-independent

Status: Accepted

## Context

Crosseno needs common concepts that can be consumed by generators, lexicons, clue systems, publication formats, and CMS adapters without creating dependency cycles or embedding a regional crossword convention.

## Decision

Core owns exact caller-defined cells, answers, stable keys, placements, grids, crossword entries, aggregate invariants, explicit structural-validation policies, and a versioned domain snapshot. It compares Unicode strings byte-for-byte after confirming valid UTF-8. It performs no normalization or tokenization. Hard resource limits are caller supplied at every allocation boundary that can be influenced by external data.

Core does not own clues, language rules, generation/search, randomness, databases, learning metadata, external publication fields, Drupal, or WordPress. The core snapshot has its own name and version and is not the canonical publication document owned by `crosseno/formats`.

## Consequences

Consumers must tokenize and normalize before constructing `Answer`, choose all structural policies explicitly, and layer their metadata outside the core snapshot. In return, core remains deterministic, dependency-free, reusable across languages and CMS products, and safe to load under caller-defined limits.
