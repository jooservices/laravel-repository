# AI Skills

## Purpose

Agents working in this package should follow JOOservices workspace policy and
the package `AGENTS.md`. Keep docs, tests, and release notes aligned when
request-query or repository behavior changes.

Current package behavior agents must respect:

- request-query allowlists and strict mode
- relation return-type / `$trustedRelations` rules
- query profile baseline semantics
- named-filter isolation with promoted joins and other transformations
- cursor pagination projection / Expression preservation
- typed cache-key contracts

## Entry points

- Package: [`AGENTS.md`](../../AGENTS.md)
- Workspace: root `AGENTS.md` and `.ai/skills/`
- Optional GitHub skill notes under `.github/skills/` when present

Do not invent parallel skill trees under `ai/` or tool-specific command copies
that duplicate or weaken workspace policy.

## When request-query behavior changes

Update together:

- parser and trait code under `src/Support/` and `src/Traits/`
- regression tests (use Faker for test data)
- user-facing docs (`docs/02-user-guide/request-query.md`, cookbook)
- `CHANGELOG.md` / upgrade notes when the change is user-visible
