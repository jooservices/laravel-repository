---
name: release-management
description: "Use when editing tag-driven release automation, changelog flow, or release-readiness docs."
---

# Release Management

## Release baseline

- Releases are tag-driven through `vX.Y.Z`.
- The release workflow validates with `composer validate`, `composer audit`, `composer lint:all`, and `composer ci` before creating the GitHub Release.
- Packagist is not updated by the release workflow; refresh registry metadata manually when needed.

## Release-readiness checklist

1. Confirm tests pass through the documented command map.
2. Confirm the release PR into `master` has green CI.
3. Confirm changelog and release notes generation behavior.
4. Confirm README and development docs reflect the actual release flow.
