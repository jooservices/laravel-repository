# CI/CD

## Active workflow set

The repository workflow set is designed to include:

- `ci.yml`
- `release.yml`
- `pr-labeler.yml`
- `semantic-pr.yml`
- `scorecard.yml`
- `secret-scanning.yml`

## CI baseline

`ci.yml` should run:

- `composer audit`
- a lint matrix over Pint, PHPCS, PHPStan, PHPMD, and PHP-CS-Fixer dry-run
- tests with coverage artifact generation
- a minimum statement coverage threshold check
- pull-request dependency review when available

## Release baseline

`release.yml` is tag-driven through `vX.Y.Z` tags and runs the same quality gate as CI before creating the GitHub Release:

- `composer validate --strict`
- `composer audit`
- `composer lint:all`
- `composer ci` with the coverage threshold check

Packagist is not updated by this workflow. Refresh Packagist manually when you want the registry metadata to catch up with a new tag.

## Policy notes

- Keep the documented coverage threshold aligned with the workflow implementation.
- Codecov upload runs only when `CODECOV_TOKEN` is configured and the coverage report was generated successfully.
- `secret-scanning.yml` runs the OSS `gitleaks` CLI against the repository and uploads a SARIF report without requiring a separate Gitleaks license secret.
- If secret scanning is present but temporarily disabled, document that explicitly rather than implying active enforcement.

## Branch protection and Git flow

The repository ruleset `develop & master` protects both long-lived branches.

Triggers:

- push to `master` or `develop`
- pull requests targeting `master` or `develop`

In the approved Git flow:

- feature and normal fix PRs are validated when they target `develop`
- release and hotfix PRs are validated when they target `master`

Required checks enforced by the ruleset:

- GitGuardian Security Checks
- Security Checks
- Lint - Pint, PHPCS, PHPStan, PHPMD, PHP-CS-Fixer
- Tests & Coverage

Dependabot PRs should target `develop` through `.github/dependabot.yml`.

## Related documents

- [Release Process](./release-process.md)
