# Contributing

Contributions to **JOOservices Laravel Repository** should stay aligned with the repository's current quality gates.

## Git workflow

Branch roles:

- `master`: stable release branch; production-ready only; no direct normal-development commits
- `develop`: integration branch for features and normal fixes; source branch for `release/vX.Y.Z`
- `feature/*`: branch from latest `develop`; PR into `develop`
- `fix/*`: branch from latest `develop`; PR into `develop`
- `hotfix/*`: urgent production fix branch from `master`; PR into `master`; then merge back into `develop`
- `release/vX.Y.Z`: release branch from latest `develop`; used for release metadata and final stabilization only; PR into `master`

For normal work such as features, fixes, refactors, tests, and active documentation updates:

```bash
git checkout develop
git pull origin develop
git checkout -b feature/<short-name>
# or
git checkout -b fix/<short-name>
```

Open the pull request back into `develop`.

Release preparation uses the approved release flow:

- create `release/vX.Y.Z` from the latest `develop`
- update changelog, README, version references, and release metadata on `release/vX.Y.Z`
- open the release PR into `master`
- merge the approved release PR into `master`
- create tag `vX.Y.Z` from `master`
- merge `master` back into `develop`

Do not update changelog, README, version references, or release metadata directly on `master`.

Hotfixes are the only exception to the normal feature and fix flow:

- create the hotfix branch from `master`
- open the pull request back into `master`
- after merge, synchronize the hotfix change back into `develop`

During development:

- keep the working branch updated with its parent branch
- prefer rebasing onto the parent branch to keep history clean
- make sure the branch is conflict-free and current before opening a pull request

Never commit directly to `develop` or `master` except for explicitly approved emergency procedures.

Recommended branch naming:

- `feature/<short-name>`
- `fix/<short-name>`
- `refactor/<short-name>`
- `hotfix/<short-name>`
- `release/vX.Y.Z`
- `docs/<short-name>`
- `chore/<short-name>`

## Branch protection

The repository ruleset `develop & master` protects both long-lived branches and requires:

- pull requests before merge
- linear history
- blocking force-push
- required CI checks: GitGuardian Security Checks, Security Checks, Lint (Pint, PHPCS, PHPStan, PHPMD, PHP-CS-Fixer), and Tests & Coverage

Normal feature and fix PRs target `develop`. Release and hotfix PRs target `master`.

## Before opening a pull request

Run:

```bash
composer lint:fix
composer lint:all
composer test
composer check
```

Also confirm:

- no failing checks remain
- no unresolved warnings or notices remain in the required checks
- no debug code, temporary code, dead commented code, unrelated file changes, or formatting noise outside scope remain
- the branch is synced with its parent branch and ready for a focused PR

## Commit messages

CaptainHook enforces Conventional Commit messages in this shape:

```text
type(scope): Description
```

Valid types include:

- `feat`
- `fix`
- `docs`
- `style`
- `refactor`
- `perf`
- `test`
- `chore`
- `ci`
- `build`
- `revert`

## Pull request titles

GitHub Actions also validates PR titles with the same Conventional Commit type set and requires the subject to start with an uppercase letter.

## Pull request expectations

Before opening a PR:

- sync with the parent branch
- resolve all conflicts
- ensure tests, lint, and static analysis are clean
- keep the PR scope focused
- include a clear summary of what changed, why it changed, affected areas, test/lint evidence, and any relevant risk note

CI must pass, but CI does not replace the local responsibility to verify checks before commit or PR.

## Documentation changes

When editing docs:

- use the canonical product name `JOOservices Laravel Repository`
- use `jooservices/laravel-repository` only for the Composer package identifier
- avoid documenting behavior that is only declared in code but not wired into the runtime

## Labels and automation

The PR labeler can automatically assign labels based on changed files. Documentation-only changes should typically receive the `documentation` label.

## Related documents

- [Setup](./setup.md)
- [Coding Standards](./coding-standards.md)
- [Testing](./testing.md)
- [CI/CD](./ci-cd.md)
- [Release Process](./release-process.md)
