# Release Process

This document describes the GitHub release flow for JOOservices Laravel Repository.

## Release model

- Releases are tag-driven through `vX.Y.Z` tags.
- The release workflow is defined in `.github/workflows/release.yml`.
- A successful release run has two stages:
  1. validate the tag with the same quality gate as CI (`composer validate`, `composer audit`, `composer lint:all`, and `composer ci` with the coverage threshold)
  2. create the GitHub Release
- Do not push a release tag until the release PR into `master` has a green CI run and the maintainer checklist below is complete.
- Packagist metadata is maintained separately outside this workflow when needed.

## GitHub repository setup

### 1. Required repository settings

In the GitHub repository:

1. Open `Settings`.
2. Open `Actions` and ensure GitHub Actions are enabled.

### 2. Required workflow permissions

The checked-in release workflow already declares the required permissions:

- `contents: write`

Do not broaden these permissions unless the workflow behavior changes.

### 3. Branch and tag policy

- Protect `master` and `develop` through the `develop & master` ruleset.
- Prepare release notes on a release branch such as `release/v1.4.0` when you want an isolated release-prep change set.
- Create release tags only from the intended release commit on `master`.
- Use stable tags in the format `vX.Y.Z`.
- Pre-release tags such as `v1.2.3-beta.1` are marked as GitHub prereleases automatically.
- After tagging, merge `master` back into `develop` so both branches stay synchronized.

## Pre-release maintainer checklist

Before tagging a release:

1. Confirm the release PR into `master` has a green CI workflow run (all required ruleset checks).
2. Confirm `composer lint:all` passes locally or on CI.
3. Confirm `composer ci` passes locally or on CI.
4. Review docs for any behavior or contributor-workflow changes.
5. Confirm `CHANGELOG.md` is up to date if the release process depends on it.
6. Confirm the version tag you plan to create is correct and final.

## How to cut a release

### 1. Prepare the release commit

Make sure the release commit is already on the intended branch.

Typical release-prep updates include:

1. refresh `CHANGELOG.md` from `Unreleased` to the target version and date
2. update release-facing docs and AI guidance if workflow or examples changed
3. verify `composer validate --strict` still passes

Do not add or bump a Composer `version` field. The pushed `vX.Y.Z` tag is the release version source of truth.

### 2. Create the tag locally

Example:

```bash
git checkout release/v1.4.0
git tag v1.4.0
git push origin release/v1.4.0
git push origin v1.4.0
```

### 3. Observe the workflow

In GitHub Actions, the `Release` workflow should:

1. run the `validate` job
2. run the `release` job

## Expected workflow behavior

### Validate job

- checks out the repository
- installs Composer dependencies
- installs PCOV because the PHPUnit configuration emits coverage reports
- runs `composer validate --strict`
- runs `composer audit`
- runs `composer lint:all`
- runs `composer ci` (tests with coverage artifacts and the minimum statement coverage threshold)

### Release job

- creates a GitHub Release from the tag
- generates GitHub release notes through the release action
- does not require GitHub Discussions to be enabled

## Related documents

- `README.md`
- `docs/04-development/ci-cd.md`
- `.github/workflows/release.yml`
