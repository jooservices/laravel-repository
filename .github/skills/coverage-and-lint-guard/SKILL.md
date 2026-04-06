---
name: coverage-and-lint-guard
description: "Use when changing source, tests, tooling, or CI. Enforces the repository quality gates and command map."
---

# Coverage And Lint Guard

## Required commands

- `composer lint`
- `composer lint:all`
- `composer test`
- `composer test:coverage`
- `composer ci`

## Policy

- Prefer the smallest command set that validates the change during development.
- Repo-level changes should re-check the full `composer ci` path when practical.
- Coverage artifacts should be generated in CI-friendly form under `build/coverage`.
- Keep the configured coverage threshold and the documented threshold in sync.
