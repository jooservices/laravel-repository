# Examples

These examples show opt-in repository compositions for common package use cases. They intentionally use only behavior currently provided by JOOservices Laravel Repository.

## Basic UserRepository

```php
use App\Models\User;
use Jooservices\LaravelRepository\Contracts\RepositoryInterface;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasCrud;
use Jooservices\LaravelRepository\Traits\HasFilter;
use Jooservices\LaravelRepository\Traits\HasOrder;
use Jooservices\LaravelRepository\Traits\HasRead;

final class UserRepository extends EloquentRepository implements RepositoryInterface
{
    use HasCrud;
    use HasFilter;
    use HasOrder;
    use HasRead;

    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
```

## Allowed Request Query

```php
use Jooservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use Jooservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use Jooservices\LaravelRepository\Traits\HasRequestQuery;

final class UserRepository extends EloquentRepository implements AllowsRequestQueryInterface, RepositoryInterface
{
    use HasAllowedRequestQuery;
    use HasFilter;
    use HasOrder;
    use HasRequestQuery;

    protected ?array $allowedFilters = ['status', 'email'];
    protected ?array $allowedSorts = ['name', 'created_at'];
    protected ?array $allowedFields = ['id', 'name', 'email', 'status'];
    protected ?array $allowedIncludes = ['posts'];
    protected ?bool $requestQueryStrict = true;
}
```

In strict mode, request-controlled filter, sort, field, include, scope, and relation-filter names must be allowlisted. Unsupported request operators throw `InvalidRequestQueryException`.

## Relation Filters

```php
protected ?array $allowedRelationFilters = [
    'posts' => ['status', 'title'],
    'posts.user' => ['email'],
];
```

```php
$request = Request::create('/', 'GET', [
    'filter' => [
        'whereHas' => [
            [
                'relation' => 'posts',
                'where' => [
                    ['column' => 'status', 'operator' => 'exact', 'value' => 'published'],
                ],
            ],
        ],
    ],
]);

$users = $repository->fromRequest($request)->get();
```

## Criteria

```php
final class ActiveUsersCriteria implements CriteriaInterface
{
    public function apply(Builder $query): void
    {
        $query->where('status', 'active');
    }
}

$users = $repository
    ->pushCriteria(new ActiveUsersCriteria)
    ->orderBy(['created_at' => 'desc'])
    ->get();
```

Criteria are applied once per active builder. Popping or clearing criteria resets query state so the next terminal call starts from a fresh builder.

## Cache Wrapper

```php
$key = $repository->cacheKey('users.active.count', ['tenant' => $tenantId]);

$count = $repository->remember($key, 300, static function (UserRepository $repository): int {
    return $repository->filter(['status' => 'active'])->count();
});
```

`HasCache` is a low-level opt-in wrapper around Laravel cache. It does not automatically cache queries and does not invalidate cache entries after CRUD operations.

## Pagination And Iteration

```php
$page = $repository->fromRequest($request)->paginate(15);
$safePage = $repository->paginateFromRequest($request);

$repository->filter(['status' => 'active'])->chunk(100, function (Collection $users): void {
    // Process a chunk.
});

$stream = $repository->orderBy(['id' => 'asc'])->cursor();
$cursorPage = $repository->orderBy(['id' => 'asc'])->cursorPaginate(50);
```

`paginateFromRequest()` reads `per_page`, falls back to `default_per_page`, caps permissive requests at `max_per_page`, and throws in strict mode when the request value is invalid or too large.

## When Not To Use A Repository

Avoid adding a repository when a model query is simple, local to one controller action, and unlikely to be reused. The pattern pays for itself when you need a stable domain-facing query API, shared criteria, request-query allowlists, or a testable boundary around repeated persistence workflows.

## Security Recommendations

- Prefer `HasAllowedRequestQuery` plus strict mode for public HTTP APIs.
- Expose aliases for stable public names instead of internal column or relation names.
- Keep relation filters narrow by allowlisting both relation paths and columns.
- Keep `max_per_page` low enough for the model and endpoint.
- Add tests for every public request-query contract a controller relies on.
