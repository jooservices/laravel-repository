# Cookbook — JOOservices Laravel Repository

Short recipes for the most common API repository setups.

## 1. Scaffold a repository

```bash
php artisan make:repository UserRepository --model=User --preset=api
php artisan make:repository ReportRepository --model=Report --preset=read
php artisan make:repository CustomRepository --model=Item --preset=empty
```

Presets:

| Preset | Base | Contract | Traits |
| --- | --- | --- | --- |
| `api` (default) | `ApiRepository` | `RepositoryInterface` | CRUD, filter, order, read, request-query, allowlists, profiles, debug |
| `read` | `ReadRepository` | `ReadRepositoryInterface` (no CRUD) | filter, order, read, request-query, allowlists, profiles, debug |
| `empty` | `EloquentRepository` | (compose yourself) | CRUD + filter + order only |

`ReadRepository` intentionally implements `ReadRepositoryInterface`, not
`RepositoryInterface`, so read-only presets stay free of create/update/delete.

## 2. Strict API list endpoint

```php
final class UserRepository extends ApiRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);

        $this->requestQueryStrict = true;
        $this->allowedFilters = ['status', 'email'];
        $this->allowedSorts = ['created_at', 'name'];
        $this->allowedIncludes = ['posts'];
        $this->allowedFields = ['id', 'name', 'email', 'status'];
    }
}

// GET /users?filter[where][0][]=status&filter[where][0][]=active&filter[order][]=-created_at
$users = app(UserRepository::class)->paginateFromRequest($request);
```

Sort shorthand: `-created_at` means `created_at desc`. Comma lists work: `name,-id`.

## 3. Named profiles (index vs admin)

`forProfile()` restores a **repository baseline** (allowlists captured on the
first `withQueryProfiles()` / `forProfile()` call), then overlays only keys
present in the named profile. Omitted keys keep the baseline — they never widen
to unrestricted `null` unless the baseline itself was unrestricted.

Set restrictive defaults on the repository (or in the constructor) **before**
registering profiles. Explicit profile values (including `strict: false` or
`includes: null`) can still broaden access when you intend that.

```php
// Baseline: no includes, strict on
$this->allowedIncludes = [];
$this->requestQueryStrict = true;

$this->withQueryProfiles([
    'index' => [
        'filters' => ['status'],
        'sorts' => ['created_at'],
        // includes / strict omitted → stay [] and true
    ],
    'admin' => [
        'filters' => ['status', 'email', 'name'],
        'sorts' => ['created_at', 'name', 'id'],
        'includes' => ['posts'],
        'strict' => true,
    ],
]);

$users = $repo->forProfile('index')->paginateFromRequest($request);
```

## 4. Ignore / default / nullable filter values

```php
// On HasRequestQueryMetadata:
protected array $valueRules = [
    'filters' => [
        'status' => [
            ['rule' => 'ignore', 'values' => ['', '*', 'all']],
            ['rule' => 'default', 'value' => 'active'],
        ],
        'deleted' => ['nullable'],
    ],
];
```

`ignore` skips the clause. `default` fills empty/null. `nullable` turns empty string into `null`.

## 5. Debug SQL

```php
$repo->filter(['status' => 'active'])->orderBy(['-created_at']);
$sql = $repo->toSql();
$payload = $repo->toQuery(); // ['sql' => ..., 'bindings' => [...]]
// $repo->ddQuery(); // dump + die
```

## 6. Soft deletes + locking

```php
use HasSoftDeletes;
use HasLocking;

$repo->onlyTrashed()->get();
$repo->restore($id);
$repo->lockForUpdate()->findByOrFail(['email' => $email]);
```

Model must use `Illuminate\Database\Eloquent\SoftDeletes`.

## 7. Extra read / write helpers

```php
$repo->findMany([1, 2, 3]);
$repo->findBy(['email' => $email]);
$repo->updateOrCreate(['email' => $email], ['name' => $name]);
$repo->upsert([...], ['email'], ['name']);
$repo->pluck('email');
$repo->sum('amount');
$repo->simplePaginate(25);
$repo->cursorPaginateFromRequest($request);
$repo->clearOrders();
```

## 8. Operators

Request / `Filter` operators include: `exact`, `partial`, `beginsWith`, `endsWith`,
`before`, `after`, `date`, `jsonContains`, plus `eq`/`neq`/`gt`/`gte`/`lt`/`lte`/`like`.
