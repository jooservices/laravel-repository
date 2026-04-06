---
name: release-management
description: "Use when editing tag-driven release automation, changelog flow, Packagist publication, or release-readiness docs."
---

# Release Management

## Release baseline

- Releases are tag-driven through `vX.Y.Z`.
- The release workflow should validate tests before creating artifacts.
- Publishing steps must depend on documented repository secrets and verified external endpoints.

## Packagist rule

Packagist publication in this repository depends on:

- `PACKAGIST_TOKEN`
- Packagist username `jooservices`
- repository URL `https://github.com/jooservices/laravel-repository`
- a publish step that runs only after the release job succeeds for stable version tags

Document the secret names, repository URL, triggering step, and failure mode whenever this flow changes.

## Release-readiness checklist

1. Confirm tests pass through the documented command map.
2. Confirm changelog and release notes generation behavior.
3. Confirm required secrets are documented and provisioned.
4. Confirm README and development docs reflect the actual release flow.
