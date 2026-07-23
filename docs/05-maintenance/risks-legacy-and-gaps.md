# Risks, Legacy, And Gaps

## Current boundaries

- The package intentionally exposes repository behavior through opt-in traits rather than one always-on implementation.
- Query state reset semantics depend on the filtering chain and should not be removed casually.
- Request-query support is limited to the implemented clause families.
- Strict request-query mode requires explicit allowlists for request-controlled names.
- `HasCache` is a low-level opt-in wrapper and does not automatically cache queries or invalidate CRUD writes.

## Namespace mismatch

Current public code and examples use the namespace `Jooservices\LaravelRepository`.

The broader JOOservices ecosystem style prefers `JOOservices\*`. Renaming this package namespace would break existing consumers and should be considered only for a future major release with a migration plan and compatibility notes.

## Governance rollout risks

- Raising lint rigor may expose existing issues that were previously tolerated.
- A hard 90% coverage gate may require staged adoption if the current baseline is lower.
- Hook and workflow additions introduce more contributor tooling and should stay documented.
- PHPStan remains at level `6`. Raising it further (for example to level `7` with full repository generics) must be an explicit typing pass, not a drive-by config change.
- Sparse `fields` + root `with` projections preserve BelongsTo/MorphTo owner keys; nested include edge cases should still be regression-tested when relation metadata is customized.
