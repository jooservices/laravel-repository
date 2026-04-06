# AI Skills

## Purpose

The repository AI skill pack helps contributors and agents:

- follow repository-native code style and design principles
- understand class and module ownership before editing
- route work through the canonical command map
- classify change types and workflow intent before implementation
- review risk, release readiness, and security implications for repo-governance changes
- keep docs, tests, workflows, and release policy aligned

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

Those canonical skills are adapted for:

- Cursor in `.cursor/rules/`
- Claude Code in `.claude/commands/`
- VS Code Copilot in `.github/copilot-instructions.md`, `.github/instructions/`, and `.github/prompts/`
- JetBrains in `jetbrains/prompts/`
- Antigravity in `antigravity/prompts/`
