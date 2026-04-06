---
name: task-routing-and-intent-map
description: "Use when first classifying a task in jooservices/laravel-repository. Distinguishes package code, docs, CI, AI customization, and release work before editing."
---

# Task Routing And Intent Map

## Routing questions

- Is the task changing runtime package behavior, repository governance, or only documentation?
- Does the task touch source modules, workflows, hooks, prompts, or release files?
- Is the work implementation, review, triage, or authoring?

## Route to the right surfaces

- Package code: `src/`, tests, and user docs.
- Documentation work: `README.md`, `docs/`, examples, and AI usage notes.
- Governance work: `.github/workflows/`, hook config, ignore files, release docs.
- AI customization work: `.github/skills/`, `.github/prompts/`, `.claude/commands/`, `.cursor/rules/`, `jetbrains/prompts/`, `antigravity/prompts/`.

## Default follow-up skills

- Package code: `architecture-and-design-principles`, `class-purpose-and-module-map`, `coverage-and-lint-guard`
- Docs: `documentation-sync`, `review-and-risk-assessment`
- CI and release: `ci-hooks-maintenance`, `security-hardening`, `release-management`
