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

`release.yml` is tag-driven through `vX.Y.Z` tags and should validate tests before publishing release artifacts.

Packagist publishing is active for stable version tags and depends on this repository secrets contract:

- `PACKAGIST_TOKEN`: a Packagist API token with `update-package` access
- Packagist username used by the workflow: `jooservices`
- repository URL sent to Packagist: `https://github.com/jooservices/laravel-repository`
- trigger point: after the GitHub release job succeeds for a stable `vX.Y.Z` tag
- failure mode: the `publish` job fails the workflow if the token is missing or the Packagist update request fails

## Policy notes

- Keep the documented coverage threshold aligned with the workflow implementation.
- Codecov upload runs only when `CODECOV_TOKEN` is configured and the coverage report was generated successfully.
- `secret-scanning.yml` runs the OSS `gitleaks` CLI against the repository and uploads a SARIF report without requiring a separate Gitleaks license secret.
- If secret scanning is present but temporarily disabled, document that explicitly rather than implying active enforcement.

## Related documents

- [Release Process](./release-process.md)
