# Usage guide

## Installation

### Requirements

- PHP ^8.5
- Laravel ^12.0
- illuminate/contracts, illuminate/database, illuminate/support, illuminate/http ^12.0

### Install via Composer

```bash
composer require jooservices/laravel-repository
```

The package registers its service provider automatically (Laravel package discovery).

### Publish config (optional)

```bash
php artisan vendor:publish --tag=laravel-repository-config
```

This creates `config/laravel-repository.php` with:

- `default_per_page`: default for `paginate()` (default: 15)
- `request_key`: key used for request input (`filter` or `query`)

---

## Trait-based composition

Use only the traits your repository needs:

| Need | Implement | Use traits |
|------|------------|------------|
| CRUD only | `CrudRepositoryInterface` | `HasCrud` |
| Filter + get | `FilterableRepositoryInterface` | `HasFilter` |
| Filter + order | `FilterableRepositoryInterface`, `OrderableRepositoryInterface` | `HasFilter`, `HasOrder` |
| Full CRUD + filter + order | `RepositoryInterface` | `HasCrud`, `HasFilter`, `HasOrder` |
| Query from request | `RequestQueryRepositoryInterface` | `HasFilter`, `HasOrder`, `HasRequestQuery` |
| Full (CRUD + filter + order + fromRequest) | `RepositoryInterface` | `HasCrud`, `HasFilter`, `HasOrder`, `HasRequestQuery` |

---

## Creating a repository

### Example: CRUD + filter + order

```php
namespace App\Repositories;

use App\Models\User;
use Jooservices\LaravelRepository\Contracts\RepositoryInterface;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasCrud;
use Jooservices\LaravelRepository\Traits\HasFilter;
use Jooservices\LaravelRepository\Traits\HasOrder;

class UserRepository extends EloquentRepository implements RepositoryInterface
{
    use HasCrud;
    use HasFilter;
    use HasOrder;

    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
```

### Example: full stack (including fromRequest)

```php
use Jooservices\LaravelRepository\Traits\HasRequestQuery;

class UserRepository extends EloquentRepository implements RepositoryInterface
{
    use HasCrud;
    use HasFilter;
    use HasOrder;
    use HasRequestQuery;

    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
```

Inject the repository in your controller (or bind it in a service provider):

```php
public function __construct(
    private UserRepository $userRepository
) {}
```

---

## CRUD

| Method | Description |
|--------|-------------|
| `find($id)` | Returns model or null |
| `findOrFail($id)` | Returns model or throws |
| `all()` | Returns collection of all models |
| `create($data)` | Creates and returns model |
| `update($id, $data)` | Updates model, returns bool |
| `delete($id)` | Deletes model, returns bool |

Example:

```php
$user = $this->userRepository->find($id);
$user = $this->userRepository->findOrFail($id);
$all = $this->userRepository->all();
$user = $this->userRepository->create(['name' => 'John', 'email' => 'john@example.com']);
$this->userRepository->update($id, ['name' => 'Jane']);
$this->userRepository->delete($id);
```

---

## Filter and order

### Array syntax

```php
$users = $this->userRepository
    ->filter(['status' => 'active'])
    ->orderBy(['created_at' => 'desc'])
    ->get();

$users = $this->userRepository
    ->filter(['status' => 'active'])
    ->paginate(15);
```

### Value objects (Filter, Order)

For custom operators (e.g. `like`):

```php
use Jooservices\LaravelRepository\Support\Filter;
use Jooservices\LaravelRepository\Support\Order;

$users = $this->userRepository
    ->filter([
        new Filter('status', 'active'),
        new Filter('name', '%john%', 'like'),
    ])
    ->orderBy([new Order('created_at', 'desc')])
    ->get();
```

---

## Query from request

Send filters and order via request input under the key `filter` or `query`.

### URL example

```
?filter[where][0][column]=status&filter[where][0][value]=active
&filter[order][0][column]=created_at&filter[order][0][direction]=desc
```

### Supported keys

- **where**: array of `column`, `value`, optional `operator` (default `=`)
- **orWhere**: same structure
- **whereIn**: `column`, `values` (array)
- **whereBetween**: `column`, `range` (array of two values)
- **whereNull**: array of column names
- **whereNotNull**: array of column names
- **with**: array of relation names (eager load)
- **order**: array of `column`, `direction` (`asc`/`desc`)

### In the controller

```php
$users = $this->userRepository->fromRequest($request)->paginate(15);
```

You can chain after `fromRequest()`: e.g. `fromRequest($request)->filter([...])->orderBy([...])->get()`.

---

## Custom filter (FilterInterface)

Implement `FilterInterface` for custom logic:

```php
use Illuminate\Database\Eloquent\Builder;
use Jooservices\LaravelRepository\Contracts\FilterInterface;

class StatusFilter implements FilterInterface
{
    public function __construct(private string $status) {}

    public function apply(Builder $query): void
    {
        $query->where('status', $this->status);
    }
}
```

Use it in `filter()`:

```php
->filter([new StatusFilter('active')])
```

---

## Testing and quality

Run the full check (style, PHPCS, PHPMD, PHPStan, tests with coverage):

```bash
composer check
```

Or individually:

```bash
composer lint           # Pint + PHPCS + PHPMD + PHPStan
composer phpcs          # PHP CodeSniffer
composer phpmd          # PHPMD
composer phpstan        # PHPStan
composer test           # PHPUnit
composer test:coverage  # PHPUnit with coverage
```

---

## See also

- [Architecture](architecture.md) — structure and design
- [Process & logic flow](process-flow.md) — flow and diagrams
