# AI Skills

## Purpose

The repository AI skill pack helps contributors and agents:

- follow repository-native code style and design principles
- understand class and module ownership before editing
- route work through the canonical command map
- classify change types and workflow intent before implementation
- review risk, release readiness, and security implications for repo-governance changes
- keep docs, tests, workflows, and release policy aligned

It also keeps agents aligned with the current package behavior, especially around request-query support. Today that includes:

- request-query allowlists and strict mode
- strict operator validation and request per-page guards
- request-driven fields, named request filters, and callback micro filters
- filter aliases and relation aliases
- scope filters and scope definitions with parameter-count enforcement
- relation count clauses
- eager-load includes plus derived `Count` and `Exists` helpers and metadata-defined sum or avg or min or max aggregate helpers
- request value-normalization rules for filters, scopes, named filters, and relation filters
- nested relation filters such as `whereHas` and `whereDoesntHave` variants
- criteria/query state lifecycle and low-level cache wrapper boundaries

## Entry points

Start with:

- `AGENTS.md`
- `CLAUDE.md`
- `ai/skills/README.md`
- `ai/skills/USAGE.md`

## Canonical skill source

The source of truth lives in:

- `.github/skills/`

Recommended finer-grained workflow skills include:

- task routing and intent mapping
- change type taxonomy
- review and risk assessment
- commit and PR authoring
- security hardening
- release management

For release preparation work, keep these surfaces aligned in the same change when applicable:

- `composer.json` package metadata
- `CHANGELOG.md`
- release-facing docs under `docs/04-development/`
- AI-facing usage notes and prompt adapters when release workflow guidance changes

## When request-query behavior changes

Agents should treat request-query work as a cross-surface change.

Update together:

- parser and trait code under `src/Support/` and `src/Traits/`
- strict-mode and allowlist tests
- pagination guard, operator validation, lifecycle, and cache wrapper tests when those areas change
- user-facing docs for request-query support
- AI-facing docs and instructions if repository truth or contributor guidance changed

The AI docs should never describe request-query clause families beyond what is implemented and covered by tests.

Those canonical skills are adapted for:

- Cursor in `.cursor/rules/`
- Claude Code in `.claude/commands/`
- VS Code Copilot in `.github/copilot-instructions.md`, `.github/instructions/`, and `.github/prompts/`
- JetBrains in `jetbrains/prompts/`
- Antigravity in `antigravity/prompts/`
