# jooservices/laravel-repository

[![CI](https://github.com/jooservices/laravel-repository/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/jooservices/laravel-repository/actions/workflows/ci.yml)
[![Coverage (develop)](https://codecov.io/gh/jooservices/laravel-repository/branch/develop/graph/badge.svg)](https://codecov.io/gh/jooservices/laravel-repository/branch/develop)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/jooservices/laravel-repository/badge)](https://securityscorecards.dev/viewer/?uri=github.com/jooservices/laravel-repository)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-blue.svg)](https://www.php.net/)
[![GitHub Release](https://img.shields.io/github/v/release/jooservices/laravel-repository?display_name=tag)](https://github.com/jooservices/laravel-repository/releases)
[![Packagist Version](https://img.shields.io/packagist/v/jooservices/laravel-repository)](https://packagist.org/packages/jooservices/laravel-repository)
[![Total Downloads](https://img.shields.io/packagist/dt/jooservices/laravel-repository)](https://packagist.org/packages/jooservices/laravel-repository)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

`jooservices/laravel-repository` is a PHP `^8.5` Laravel package for composing Eloquent repositories from opt-in traits, with CRUD, filtering, ordering, and request-driven query capabilities.

> **Upgrade note:** The current release line is **v4.0.0**. Review [UPGRADE-4.0.md](UPGRADE-4.0.md) before upgrading from `1.x`; the major release includes API and behavior changes.

## Features

- Compose repository capabilities with traits and segregated contracts.
- Use the `ApiRepository` and `ReadRepository` presets or generate a repository with Artisan.
- Add CRUD, filters, ordering, criteria, soft deletes, locking, cache wrappers, and cursor pagination as needed.
- Build request-driven queries with allowlists, strict mode, named filters, field selection, scopes, relation filters, aggregate includes, and query profiles.
- Reuse `Filter` and `Order` value objects.

## Requirements

- PHP `^8.5`
- Laravel 12 or 13
- Composer

## Installation

```bash
composer require jooservices/laravel-repository:^4.0
```

Laravel package discovery registers the service provider. To publish the optional config or repository stubs:

```bash
php artisan vendor:publish --tag=laravel-repository-config
php artisan vendor:publish --tag=laravel-repository-stubs
```

## Quick start

Extend the API preset for a repository with CRUD, filtering, ordering, reads, and request-query support:

```php
use App\Models\User;
use JOOservices\LaravelRepository\Repositories\Presets\ApiRepository;

/** @extends ApiRepository<User> */
final class UserRepository extends ApiRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}

$repository = app(UserRepository::class);
$user = $repository->find($id);
$users = $repository
    ->filter(['status' => 'active'])
    ->orderBy(['-created_at'])
    ->paginate(15);
```

Or scaffold a repository with the `api` preset:

```bash
php artisan make:repository UserRepository --model=User --preset=api
```

## Design notes

- Capabilities are opt-in; the package does not apply repository behavior globally.
- Fluent query state is lazy and resets after terminal operations such as `get()` and `paginate()`.
- Request-query clauses and relation discovery follow the supported rules in [Request Query Support](docs/02-user-guide/request-query.md).

## Documentation

- [Documentation hub](docs/README.md)
- [Installation](docs/01-getting-started/installation.md)
- [Quick start](docs/01-getting-started/quick-start.md)
- [Trait-based composition](docs/02-user-guide/trait-based-composition.md)
- [CRUD, filtering, and ordering](docs/02-user-guide/crud-filter-order.md)
- [Request-query support](docs/02-user-guide/request-query.md)
- [Examples](docs/03-examples/README.md) and [cookbook](docs/03-examples/cookbook.md)
- [Upgrade guide](UPGRADE-4.0.md)
- [Changelog](CHANGELOG.md)
- [Workflow reference](WORKFLOWS.md)

## Development

Install dependencies and run the configured quality commands:

```bash
composer install
composer lint
composer test
composer check
composer ci
```

See [contributor guidance](CONTRIBUTING.md), [development setup](docs/04-development/setup.md), [coding standards](docs/04-development/coding-standards.md), [testing](docs/04-development/testing.md), and [CI/CD](docs/04-development/ci-cd.md).

## Community

- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Support](SUPPORT.md)
- [Governance](GOVERNANCE.md)

## License

This project is licensed under the [MIT License](LICENSE).
