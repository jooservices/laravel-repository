# Release Process

This document describes the exact GitHub and Packagist setup required for maintainers to publish a release for JOOservices Laravel Repository.

## Release model

- Releases are tag-driven through `vX.Y.Z` tags.
- The release workflow is defined in `.github/workflows/release.yml`.
- A successful release run has three stages:
  1. validate the tag against the test suite
  2. create the GitHub Release
  3. notify Packagist to refresh package metadata

## GitHub repository setup

### 1. Required repository settings

In the GitHub repository:

1. Open `Settings`.
2. Open `Actions` and ensure GitHub Actions are enabled.
3. Open `Secrets and variables`, then `Actions`.
4. Add this repository secret:
   - `PACKAGIST_TOKEN`

### 2. Required workflow permissions

The checked-in release workflow already declares the required permissions:

- `contents: write`
- `discussions: write`

Do not broaden these permissions unless the workflow behavior changes.

### 3. Recommended branch and tag policy

- Protect `master` and any long-lived integration branches your team actually uses.
- Prepare release notes on a release branch such as `release/v1.2.0` when you want an isolated release-prep change set.
- Create release tags only from the intended release commit.
- Use stable tags in the format `vX.Y.Z` for releases that should notify Packagist.
- Pre-release tags such as `v1.2.3-beta.1` should not trigger the Packagist publish step.

## Packagist setup

### 1. Confirm the package exists

In Packagist, confirm that the package identifier is:

- `jooservices/laravel-repository`

### 2. Confirm the repository URL

The release workflow sends this repository URL to Packagist:

- `https://github.com/jooservices/laravel-repository`

That URL must match the repository connected to the Packagist package.

### 3. Create the API token

In Packagist:

1. Sign in as the maintainer account for `jooservices`.
2. Open the account settings or API token section.
3. Create a token that can call the `update-package` endpoint for the package.
4. Store that token in GitHub as the `PACKAGIST_TOKEN` repository secret.

The workflow assumes the Packagist username is `jooservices`.

## Pre-release maintainer checklist

Before tagging a release:

1. Confirm `composer lint:all` passes.
2. Confirm `composer test` passes.
3. Review docs for any behavior or contributor-workflow changes.
4. Confirm `CHANGELOG.md` is up to date if the release process depends on it.
5. Confirm the `PACKAGIST_TOKEN` secret still exists and is valid.
6. Confirm the version tag you plan to create is correct and final.

## How to cut a release

### 1. Prepare the release commit

Make sure the release commit is already on the intended branch.

Typical release-prep updates include:

1. refresh `CHANGELOG.md` from `Unreleased` to the target version and date
2. update release-facing docs and AI guidance if workflow or examples changed
3. verify `composer validate --strict` still passes

Do not add or bump a Composer `version` field. Packagist and the GitHub release workflow should treat the pushed `vX.Y.Z` tag as the release version source.

### 2. Create the tag locally

Example:

```bash
git checkout release/v1.2.0
git tag v1.2.0
git push origin release/v1.2.0
git push origin v1.2.0
```

### 3. Observe the workflow

In GitHub Actions, the `Release` workflow should:

1. run the `validate` job
2. run the `release` job
3. run the `publish` job for stable tags

## Expected workflow behavior

### Validate job

- checks out the repository
- installs Composer dependencies
- runs `composer test`

### Release job

- creates a GitHub Release from the tag
- generates GitHub release notes through the release action

### Publish job

- runs only for stable tags like `v1.2.3`
- fails immediately if `PACKAGIST_TOKEN` is missing
- calls Packagist `update-package`
- uses the hardcoded Packagist username `jooservices`
- sends the GitHub repository URL to Packagist

## Failure modes and recovery

### Missing `PACKAGIST_TOKEN`

Symptoms:

- the `publish` job fails with a missing-secret error

Recovery:

1. add or correct the `PACKAGIST_TOKEN` repository secret
2. rerun the failed workflow job if appropriate, or push a new stable tag if your release policy requires immutability

### Invalid or expired Packagist token

Symptoms:

- the `publish` job reaches the curl step and fails

Recovery:

1. create a new Packagist token
2. replace the `PACKAGIST_TOKEN` repository secret
3. rerun the publish step or perform a manual Packagist update

### Repository URL mismatch in Packagist

Symptoms:

- Packagist update succeeds poorly or the package metadata does not refresh as expected

Recovery:

1. verify the Packagist package points to `https://github.com/jooservices/laravel-repository`
2. fix the Packagist package configuration if needed
3. trigger another update request

## Manual fallback

If the release workflow creates the GitHub Release but Packagist update fails, you can still refresh Packagist manually after fixing credentials or configuration.

## Related documents

- `README.md`
- `docs/04-development/ci-cd.md`
- `.github/workflows/release.yml`
