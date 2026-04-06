# JOOservices Laravel Repository Instructions

This repository is a PHP 8.5+ Laravel package named `jooservices/laravel-repository`.

## Core intent

- Preserve the existing package architecture before introducing abstractions.
- Favor minimal changes that fit the current modules under `src/`.
- Treat docs and tests as part of the implementation, not follow-up work.
- Keep the repository trait-based: repositories compose behavior intentionally rather than inheriting a kitchen-sink base class.

## Repository quality rules

- Formatting authority: `Pint`
- Narrow PHPDoc cleanup: `PHP-CS-Fixer`
- Structural checks: `PHPCS`
- Static analysis: `PHPStan`
- Maintainability checks: `PHPMD`
- Tests: `PHPUnit`

## Required command map

- `composer lint`
- `composer lint:all`
- `composer lint:fix`
- `composer test`
- `composer test:coverage`
- `composer ci`

Never invent alternative commands such as `composer fix`.

## Agent-first guidance

Before making non-trivial changes, also read:

- `.github/skills/repo-quality-foundation/SKILL.md`
- `.github/skills/code-style-and-conventions/SKILL.md`
- `.github/skills/architecture-and-design-principles/SKILL.md`
- `.github/skills/class-purpose-and-module-map/SKILL.md`
- `.github/skills/documentation-sync/SKILL.md`
- `.github/skills/coverage-and-lint-guard/SKILL.md`
- `.github/skills/ci-hooks-maintenance/SKILL.md`

## Coverage and CI

- CI runs `composer audit`, a lint matrix, tests with coverage artifacts, and optional dependency review on pull requests.
- CI should enforce a minimum statement coverage threshold. The initial target is `90%` unless the repository baseline forces a staged rollout.
- Release is tag-driven through `vX.Y.Z` tags.

## Hooks and Git hygiene

- CaptainHook validates Conventional Commits on `commit-msg`.
- `pre-commit` runs staged secret scanning, Pint, PHPCS, and PHPStan.
- `pre-push` runs repository secret scanning and `composer test`.
- PR titles should use Conventional Commit types and start with an uppercase subject.

## Runtime truth guards

Do not describe behavior beyond what the package currently supports in code and tests.

Keep these package boundaries explicit:

- Query state is created lazily in `EloquentRepository` and should be cleared after terminal filter operations.
- Traits own behavior slices: CRUD, filtering, ordering, and request-query composition remain separate concerns.
- `RequestQueryParser` only supports the currently implemented clause families.
- Repositories are opt-in compositions; no feature should be described as globally active unless the repository actually uses the matching trait.

## Documentation policy

- Use the canonical product name `JOOservices Laravel Repository`.
- Use `jooservices/laravel-repository` only for the Composer package identifier.
- When public behavior changes, update docs and examples in the same change.
- Keep the docs hub, development docs, and AI instructions aligned with the same command map and package boundaries.

## Change checklist

1. Keep the change minimal and module-appropriate.
2. Add unit tests and integration tests where flow crosses boundaries.
3. Run the relevant lint and test commands.
4. Re-check docs, examples, CI assumptions, and release impact.
5. Use Conventional Commits for commits and PR titles.
