---
name: ci-hooks-maintenance
description: "Use when editing workflows, hooks, release automation, PR policy, or secret scanning. Covers repository governance surfaces beyond package runtime code."
---

# CI Hooks Maintenance

## Workflow baseline

- CI runs security, lint, coverage tests, and optional dependency review.
- Release runs from `vX.Y.Z` tags.
- PR hygiene includes labels and semantic title checks.
- Scorecard and secret scanning are repository-governance layers and must be documented truthfully.

## Hook baseline

- `commit-msg`: Conventional Commit validation
- `pre-commit`: staged secret scanning plus fast lint commands
- `pre-push`: repository secret scanning plus tests

## Change rules

- Keep workflow docs, actual workflow files, and hook config aligned.
- If a security or secret-scan workflow is present but intentionally disabled, document that explicitly.
