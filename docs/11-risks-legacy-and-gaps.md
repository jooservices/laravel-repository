# Risks, Legacy, And Gaps

## Current boundaries

- The package intentionally exposes repository behavior through opt-in traits rather than one always-on implementation.
- Query state reset semantics depend on the filtering chain and should not be removed casually.
- Request-query support is limited to the implemented clause families.

## Governance rollout risks

- Raising lint rigor may expose existing issues that were previously tolerated.
- A hard 90% coverage gate may require staged adoption if the current baseline is lower.
- Hook and workflow additions introduce more contributor tooling and should stay documented.
- Ratcheting PHPStan beyond level `6` should be handled as explicit typing work, not as a drive-by config change.
- Packagist publication now depends on repository secrets being provisioned correctly; a missing or invalid secret will fail the `publish` job after GitHub release creation.
