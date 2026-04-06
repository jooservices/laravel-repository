---
name: repo-quality-foundation
description: "Use when starting any non-trivial task in jooservices/laravel-repository. Establish repository baseline, command map, package truth, and done criteria."
---

# Repository Quality Foundation

## Baseline

- Read `AGENTS.md` before other repository-specific work.
- Use `composer lint`, `composer lint:all`, `composer lint:fix`, `composer test`, `composer test:coverage`, and `composer ci` as the authoritative command map.
- Treat docs and tests as part of the implementation.

## Package truth

- The package is trait-based and intentionally composable.
- `EloquentRepository` owns model storage and lazy query creation.
- `HasFilter` clears query state after `get()` and `paginate()`.
- `RequestQueryParser` supports only the currently implemented clause families.

## Done criteria

1. The change stays within the correct module.
2. Docs and examples reflect the final behavior.
3. Relevant lint and tests are run or the blocker is explicitly stated.
4. CI, release, and hook impact is considered when repo-level files change.
