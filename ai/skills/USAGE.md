# AI Skills Usage Guide

## Start here

For any non-trivial task, agents should treat these files as the shared baseline:

- `AGENTS.md`
- `CLAUDE.md`
- `ai/skills/README.md`

The canonical repository skills live under `.github/skills/`.

## What this skill pack is for

This pack is designed to help agents:

- understand repository-native code style
- follow architecture and design principles
- choose the right module before editing
- classify the change correctly
- implement safely with tests and docs
- review risk before shipping
- respect CI, hooks, release, and secret-scanning policy

## Recommended workflow for agents

1. Read `AGENTS.md`.
2. Route the task with `.github/skills/task-routing-and-intent-map/SKILL.md`.
3. Classify the change with `.github/skills/change-type-taxonomy/SKILL.md`.
4. Load the task-specific skills from `.github/skills/`.
5. Implement or review the change.
6. Re-check docs, tests, risk, and release impact before finishing.

## Common task recipes

### Change request-query behavior

Use:

- `architecture-and-design-principles`
- `class-purpose-and-module-map`
- `documentation-sync`
- `coverage-and-lint-guard`

Check that the change stays truthful to the current request-query surface:

- allowlists and strict mode stay opt-in
- aliases and scope definitions remain explicit metadata, not implicit global behavior
- aggregate include helpers and value rules remain explicit metadata, not implicit global behavior
- `RequestQueryParser` docs list only implemented clause families
- tests cover parser output, permissive behavior, and strict-mode failures

### Update docs

Use:

- `documentation-sync`
- `architecture-and-design-principles`
- `review-and-risk-assessment`

### Triage CI, hooks, or release work

Use:

- `ci-hooks-maintenance`
- `security-hardening`
- `release-management`
- `review-and-risk-assessment`

### Prepare a release or PR description

Use:

- `commit-and-pr-authoring`
- `review-and-risk-assessment`
- `release-management`

For a release-prep change, also verify that:

- `composer.json` carries the intended release version
- `CHANGELOG.md` matches the release line
- release docs and examples use the intended branch and tag naming
- AI-facing guidance still matches the documented release flow

## Tool-specific usage

### VS Code with Copilot

- Baseline instructions: `.github/copilot-instructions.md`
- Extra instructions: `.github/instructions/repo-quality.instructions.md`
- Prompt files: `.github/prompts/*.prompt.md`

When request-query behavior changes, keep these docs in sync at the same time:

- `README.md`
- `docs/README.md`
- `docs/02-user-guide/request-query.md`
- `docs/12-competitive-comparison-and-roadmap.md`
- AI-facing guidance files under `ai/skills/` and `.github/`

### Cursor

- Always-on baseline: `.cursor/rules/00-repo-quality-foundation.mdc`

### Claude Code

- Always-on repo guidance: `CLAUDE.md`
- Task-specific commands: `.claude/commands/*.md`

Recommended commands:

- `package-change`
- `quality-check`
- `docs-sync`
- `ci-triage`
- `security-review`
- `release-readiness`

## Prompting tips for teammates

- State the task type clearly: feature, bugfix, docs, CI, review, or release.
- Name the affected surface: traits, request parsing, contracts, workflows, or docs.
- If behavior changes, ask the agent to include tests, docs impact, compatibility notes, and a short risk summary.

### JetBrains AI Assistant

- Use `jetbrains/prompts/*.md` as prompt-library-ready templates

### Antigravity

- Use `antigravity/prompts/*.md` as portable prompts

## Maintenance rule

When repository behavior changes, update the canonical `.github/skills/` files first, then keep adapter layers in sync.
