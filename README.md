# jooservices/laravel-repository

[![CI](https://github.com/jooservices/laravel-repository/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/jooservices/laravel-repository/actions/workflows/ci.yml)
[![Coverage (develop)](https://codecov.io/gh/jooservices/laravel-repository/branch/develop/graph/badge.svg)](https://codecov.io/gh/jooservices/laravel-repository/branch/develop)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/jooservices/laravel-repository/badge)](https://securityscorecards.dev/viewer/?uri=github.com/jooservices/laravel-repository)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-blue.svg)](https://www.php.net/)
[![GitHub Release](https://img.shields.io/github/v/release/jooservices/laravel-repository?display_name=tag)](https://github.com/jooservices/laravel-repository/releases)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/jooservices/laravel-repository)](https://packagist.org/packages/jooservices/laravel-repository)
[![Total Downloads](https://img.shields.io/packagist/dt/jooservices/laravel-repository)](https://packagist.org/packages/jooservices/laravel-repository)

**JOOservices Laravel Repository** is a PHP 8.5+ Laravel package for trait-based
repository composition: CRUD, filtering, ordering, criteria, and request-driven
query composition on Eloquent.

Composer package: `jooservices/laravel-repository` — current line: **v4.0.0**.
Upgrading from `1.x`: see [UPGRADE-4.0.md](UPGRADE-4.0.md).

## Install

```bash
composer require jooservices/laravel-repository:^4.0
```

Optionally publish config and stubs:

```bash
php artisan vendor:publish --tag=laravel-repository-config
php artisan vendor:publish --tag=laravel-repository-stubs
```

## Quick example

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
$users = $repository->filter(['status' => 'active'])->orderBy(['-created_at'])->paginate(15);
```

Scaffold:

```bash
php artisan make:repository UserRepository --model=User --preset=api
```

See the [Cookbook](docs/03-examples/cookbook.md) for profiles, value rules, soft
deletes, locking, and debug helpers.

## What is supported today

- trait-based composition through segregated contracts and traits
- presets `ApiRepository` / `ReadRepository` and Artisan `make:repository`
- CRUD (`HasCrud`) including `findMany`, `findBy`, `updateOrCreate`, `upsert`
- filters, pagination, and `simplePaginate` (`HasFilter`)
- ordering and sort shorthand (`HasOrder`)
- request-driven queries (`HasRequestQuery`) with allowlists / strict mode
- named filters, field selection, scopes, relation filters, aggregate includes
- operators including `exact`, `partial`, `before`, `after`, `date`, `jsonContains`
- criteria (`HasCriteria`), soft deletes, locking, SQL debug
- named query profiles with restrictive baseline semantics
- cursor pagination that preserves sparse and Expression projections
- opt-in cache wrappers (`HasCache`) with stable typed cache keys
- reusable `Filter` and `Order` value objects

## Important boundaries

- capabilities are opt-in through traits; nothing is globally implied
- query state is lazy and resets after terminal operations
- `RequestQueryParser` supports only the documented clause families
- relation methods should declare a `Relation` return type (or use an explicit
  trust / allowlist path)

## Documentation

- [Documentation Hub](docs/README.md)
- [Installation](docs/01-getting-started/installation.md)
- [Quick Start](docs/01-getting-started/quick-start.md)
- [Request Query Support](docs/02-user-guide/request-query.md)
- [Examples](docs/03-examples/README.md)
- [Cookbook](docs/03-examples/cookbook.md)
- [Upgrade 4.0](UPGRADE-4.0.md)
- [Changelog](CHANGELOG.md)

## Development

```bash
composer lint
composer test
composer check
```

Contributor workflow:

- [Contributing](CONTRIBUTING.md)
- [Security Policy](SECURITY.md)
- [Setup](docs/04-development/setup.md)
- [Coding Standards](docs/04-development/coding-standards.md)
- [Testing](docs/04-development/testing.md)
- [CI/CD](docs/04-development/ci-cd.md)
- [Release Process](docs/04-development/release-process.md)

Project agents: [AGENTS.md](AGENTS.md) (workspace policy in the JOOservices root
`AGENTS.md`).

## License

This project is licensed under the [MIT License](LICENSE).
