# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.2.1]: https://github.com/jooservices/laravel-repository/releases/tag/v1.2.1
[1.2.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.2.0
[1.1.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.1.0
[1.0.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.0.0
