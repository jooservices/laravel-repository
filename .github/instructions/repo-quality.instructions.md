---
applyTo: "**"
description: "Use when working anywhere in the repository. Enforces repository-native command map, docs-and-tests sync, and trait-based architecture boundaries."
---

# Repository Quality Instruction

Use `AGENTS.md` as the always-on policy.

## Required behavior

- Prefer minimal changes that fit existing modules under `src/`.
- Keep docs and tests aligned with code changes.
- Use the canonical command map from `composer.json`: `lint`, `lint:all`, `lint:fix`, `test`, `test:coverage`, `ci`.
- Keep repository descriptions truthful to implemented behavior.

## Architecture guardrails

- Preserve trait-based composition.
- Do not collapse separate concerns into a single base repository.
- Keep query lifecycle ownership in `EloquentRepository`.
- Keep request parsing behavior in `RequestQueryParser` and document only implemented clauses.
