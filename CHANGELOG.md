# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.1.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.1.0
[1.0.0]: https://github.com/jooservices/laravel-repository/releases/tag/v1.0.0
