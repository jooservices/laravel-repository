# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- Sparse `fields` projections combined with root `with` includes now preserve `BelongsTo` foreign keys and `MorphTo` foreign key / morph type columns so eager-loaded relations hydrate correctly
- `fromRequest()` clears fluent query state on any request-query failure (including validation before a builder is created), so later queries do not inherit prior chain state
- Release validation now enforces the same statement coverage threshold as CI

### Changed

- `EloquentRepository` owns `newQueryWithCriteria()` as the criteria-aware CRUD extension hook; CRUD no longer discovers the method via `method_exists`
- `composer ci` now runs `test:coverage:check` via the shared `scripts/check-coverage.php` helper

### Security

- Bumped transitive `guzzlehttp/guzzle` to `7.15.1` (merged via Dependabot)

## [1.4.0] - 2026-07-06

### Added

- MIT `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, and GitHub issue templates
- `composer.json` support block
- `Support\RequestQueryInput` resolver for configurable request-query key priority
- `EloquentRepository::newQueryWithCriteria()` for criteria-aware fresh builders
- Contributor and branch-protection documentation under `docs/04-development/contributing.md`

### Fixed

- `request_key` config now controls `filter`/`query` read priority; both keys remain accepted
- `find`, `findOrFail`, and `all` honor pushed criteria without inheriting filter-chain state

### Changed

- Bumped GitHub Actions: `actions/labeler@v6`, `actions/dependency-review-action@v5`, `codecov/codecov-action@v7`, `softprops/action-gh-release@v3`, and `amannn/action-semantic-pull-request@48f2562`
- Bumped `phpstan/phpstan` to `^2.2`
- Dependabot now targets `develop`
- Removed unused `passesColumnGuard()` helper from `HasRequestQuery`

## [1.3.0] - 2026-06-24

### Added

- Added Laravel 13 support alongside Laravel 12: `illuminate/contracts`, `illuminate/database`, `illuminate/support`, and `illuminate/http` now accept `^12.0|^13.0`
- Added `orchestra/testbench:^11.0` to `require-dev` so the package can be tested against Laravel 13
- Added a CI test matrix running the suite against both Laravel 12 and Laravel 13

### Changed

- Updated installation and usage docs to state the Laravel 12/13 support range

## [1.2.1] - 2026-05-12

### Changed

- Refreshed GitHub metadata positioning and repository governance files for the approved release flow
- Added Dependabot automation so GitHub Actions maintenance updates continue through the normal review path
- Updated maintainer release guidance and release-readiness documentation without changing package runtime behavior

## [1.2.0] - 2026-05-11

### Added

- Added strict request operator validation and safe aliases for `eq`, `neq`, `gt`, `gte`, `lt`, `lte`, and `like`
- Added request per-page guards through `paginateFromRequest()` with configurable `max_per_page`
- Added cache store and cache-key helpers to the opt-in `HasCache` wrapper
- Added examples and maintenance docs under the numbered docs structure

### Changed

- Hardened strict request-query allowlist behavior for request-controlled names
- Reset query state through terminal operations with `try/finally`
- Expanded tests for request-query safety, pagination guards, lifecycle behavior, criteria, and cache wrappers

## [1.1.0] - 2026-04-08

### Changed

- Synchronized Composer package metadata for the `1.1.0` release line
- Refreshed release documentation and AI skill guidance to reflect the current version-prep workflow and release branch examples

## [1.0.0] - 2025-03-09

### Added

- Base `EloquentRepository` with model injection and query management
- **HasCrud** trait: `find`, `findOrFail`, `all`, `create`, `update`, `delete`
- **HasFilter** trait: `filter()`, `get()`, `paginate()` with array or `Filter` value objects
- **HasOrder** trait: `orderBy()` with array or `Order` value objects
- **HasRequestQuery** trait: `fromRequest()` to build query from request `filter`/`query` input
- Contracts: `RepositoryInterface`, `CrudRepositoryInterface`, `FilterableRepositoryInterface`, `OrderableRepositoryInterface`, `RequestQueryRepositoryInterface`, `FilterInterface`
- Support classes: `Filter`, `Order`, `RequestQueryParser`
- Config: `default_per_page`, `request_key` (publishable)
- Laravel 12 service provider with auto-discovery
- PHPUnit tests and quality tooling (Pint, PHPCS, PHPMD, PHPStan)

### Requirements

- PHP ^8.5
- Laravel ^12.0
- illuminate/contracts, illuminate/database, illuminate/support, illuminate/http ^12.0

[1.4.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.4.0
[1.3.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.3.0
[1.2.1]: https://github.com/jooservices/laravel-repository/releases/tag/v1.2.1
[1.2.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.2.0
[1.1.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.1.0
[1.0.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.0.0
