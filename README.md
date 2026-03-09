# Laravel Repository

Base repositories with CRUD, filtering, ordering, and query-from-request for Laravel 12. Built with SOLID, KISS, DRY, and YAGNI. Use only the traits you need.

## Requirements

- PHP ^8.5
- Laravel ^12.0
- illuminate/contracts, illuminate/database, illuminate/support, illuminate/http ^12.0

## Installation

```bash
composer require jooservices/laravel-repository
```

The package registers its service provider automatically. Optionally publish config:

```bash
php artisan vendor:publish --tag=laravel-repository-config
```

## Documentation

Full documentation lives in the **[./docs](docs/)** folder:

| Document | Description |
|----------|-------------|
| [**Architecture**](docs/architecture.md) | Package structure, layers, interfaces, and design principles |
| [**Process & logic flow**](docs/process-flow.md) | Data and request flow with diagrams (including Mermaid) |
| [**Usage guide**](docs/usage-guide.md) | Installation, configuration, trait selection, CRUD, filter/order, query-from-request, and testing |

Quick links from the docs:

- [Trait-based composition](docs/usage-guide.md#trait-based-composition) — which traits to use for which needs
- [Creating a repository](docs/usage-guide.md#creating-a-repository)
- [CRUD](docs/usage-guide.md#crud) · [Filter and order](docs/usage-guide.md#filter-and-order) · [Query from request](docs/usage-guide.md#query-from-request)
- [Testing and quality](docs/usage-guide.md#testing-and-quality)

## Quick example

```php
use Jooservices\LaravelRepository\Contracts\RepositoryInterface;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasCrud;
use Jooservices\LaravelRepository\Traits\HasFilter;
use Jooservices\LaravelRepository\Traits\HasOrder;

class UserRepository extends EloquentRepository implements RepositoryInterface
{
    use HasCrud, HasFilter, HasOrder;

    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
```

```php
$user = $repo->find($id);
$users = $repo->filter(['status' => 'active'])->orderBy(['created_at' => 'desc'])->paginate(15);
$users = $repo->fromRequest($request)->paginate(15);  // with HasRequestQuery
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

MIT.
