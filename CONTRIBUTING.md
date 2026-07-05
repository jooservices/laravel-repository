# Contributing

Contributions to `jooservices/laravel-repository` should keep **JOOservices Laravel Repository** aligned with its existing package architecture, repository quality gates, and contributor guidance.

For development details that go deeper than this file, see [docs/04-development/contributing.md](./docs/04-development/contributing.md), [AGENTS.md](./AGENTS.md), and [CLAUDE.md](./CLAUDE.md).

## Git workflow summary

- `master` is the stable release branch and should receive only approved release PRs and urgent hotfix PRs
- `develop` is the integration branch for normal feature and fix work
- create `feature/*` and `fix/*` branches from the latest `develop`, then open the PR back into `develop`
- create `release/vX.Y.Z` from the latest `develop` for release metadata and final stabilization, then open the PR into `master`
- after the release PR is merged into `master`, create the release tag and merge `master` back into `develop`
- use `hotfix/*` only for urgent production fixes from `master`, then merge back into both `master` and `develop`
- do not commit directly to `develop` or `master`

## Requirements

- PHP 8.5+
- Composer
- follow the repository docs and AI agent instructions before making changes

## Setup

```bash
composer install
```

## Quality gates

Use only the Composer scripts defined by the repository:

```bash
composer lint
composer lint:all
composer lint:fix
composer lint:pint
composer lint:pint:fix
composer lint:cs
composer lint:cs:fix
composer lint:phpcs
composer lint:phpstan
composer lint:phpmd
composer test
composer test:coverage
composer check
composer ci
```

Before commit or pull request, run the relevant checks for the change and make sure they pass with zero warnings or notices.

## Coding rules

- use `declare(strict_types=1);` in PHP files where repository conventions require it
- follow the package's trait-based repository architecture and repository-native standards
- keep changes consistent with SOLID, DRY, KISS, and YAGNI
- when formatting rules conflict, Pint is the source of truth
- avoid unrelated refactors or cleanup outside the requested scope

## Testing and documentation

- behavior changes require tests
- do not fake or document unsupported internal package behavior
- update docs and examples when public behavior changes
- keep coverage and quality expectations aligned with repository policy

## Pull requests

Pull requests should explain:

- what changed
- why the change is needed
- how it was tested

Also keep the pull request focused, include testing evidence, and do not leave warnings, notices, debug code, or unrelated file changes in scope.

Use the repository issue and pull request templates where available.

## Security

Do not report vulnerabilities in public issues.

Follow [SECURITY.md](./SECURITY.md) for private reporting.

## AI contributors

AI and assisted contributors must:

- inspect the real repository before making changes
- avoid assumptions when repository truth is unclear
- stop and ask when requirements or repository state are unclear or conflicting
- run the relevant checks before commit
- keep governance files, issue templates, and pull request templates aligned with repository workflow when they are touched
