# AI Skills Map

This repository keeps one quality policy and exposes it through several AI tool formats.

## Canonical repository skills

- `.github/skills/repo-quality-foundation/SKILL.md`
- `.github/skills/code-style-and-conventions/SKILL.md`
- `.github/skills/architecture-and-design-principles/SKILL.md`
- `.github/skills/class-purpose-and-module-map/SKILL.md`
- `.github/skills/task-routing-and-intent-map/SKILL.md`
- `.github/skills/change-type-taxonomy/SKILL.md`
- `.github/skills/review-and-risk-assessment/SKILL.md`
- `.github/skills/commit-and-pr-authoring/SKILL.md`
- `.github/skills/documentation-sync/SKILL.md`
- `.github/skills/coverage-and-lint-guard/SKILL.md`
- `.github/skills/ci-hooks-maintenance/SKILL.md`
- `.github/skills/security-hardening/SKILL.md`
- `.github/skills/release-management/SKILL.md`

## Adapter layers

- `AGENTS.md`: shared always-on repository policy
- `.cursor/rules/`: Cursor project rules
- `CLAUDE.md` and `.claude/commands/`: Claude Code guidance and slash commands
- `.github/copilot-instructions.md`, `.github/instructions/`, `.github/prompts/`: VS Code Copilot instructions and prompt files
- `jetbrains/prompts/`: prompt-library-ready markdown templates
- `antigravity/prompts/`: portable prompts for environments without a stable checked-in skill format

## Intent

All adapters should reflect the same repository truth:

- code style and conventions beyond formatter output
- design principles and change heuristics for agents
- class and module ownership
- implemented request-query capabilities and boundaries
- task routing and change classification
- review, risk, and PR authoring
- documentation sync
- lint and coverage gates
- hook, CI, and release policy
- security and release readiness

## Current package behavior agents should preserve

The adapter layers should stay aligned with the current package surface:

- `EloquentRepository` owns model storage and lazy query lifecycle
- traits remain explicit behavior slices rather than a monolithic base repository
- request-query support includes fields, named request filters, callback micro filters, scopes, scope definitions, filter aliases, relation aliases, relation count clauses, eager-load includes, derived `Count` or `Exists` includes, metadata-defined aggregate include helpers, value-normalization rules, and nested relation filters
- strict mode and allowlists are opt-in through repository contracts and traits
- `RequestQueryParser` documentation must describe only the clause families that are actually implemented and tested
