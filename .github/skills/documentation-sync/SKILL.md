---
name: documentation-sync
description: "Use when public behavior, contributor workflow, docs structure, or examples change. Keeps README, docs hub, and AI guidance aligned."
---

# Documentation Sync

## Update surfaces together

- Root `README.md`
- `docs/README.md`
- The numbered docs pages affected by the change
- `AGENTS.md` and AI usage guides when contributor workflow changes
- Setup, CI/CD, and release docs when workflow prerequisites or external-service assumptions change

## Documentation truth rules

- Describe only implemented behavior.
- Keep examples aligned with current contracts, traits, and request parser support.
- When commands change, update docs, hooks, workflows, and AI files in the same change.
- Keep docs in the numbered structure, including `03-examples` and `05-maintenance`.
- When request-query behavior changes, update strict-mode, operator, pagination, security, and allowlist examples together.
