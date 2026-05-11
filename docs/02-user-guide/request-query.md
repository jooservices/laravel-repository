# Request Query Support

Repositories that use `HasRequestQuery` can hydrate query constraints from request input under the `filter` or `query` key.

Repositories that also implement `AllowsRequestQueryInterface` and use `HasAllowedRequestQuery` can restrict which filters, sorts, includes, fields, scopes, relation filters, and relation count clauses are accepted.

Repositories that implement `ProvidesRequestFiltersInterface` and use `HasRequestFilters` can also map named request filters to dedicated filter classes or lightweight callbacks.

Repositories that implement `ProvidesRequestQueryMetadataInterface` and use `HasRequestQueryMetadata` can also expose public filter aliases, relation aliases, scope definitions, aggregate include helpers, and value-normalization rules.

## Supported clause families

- `where`
- `orWhere`
- `whereIn`
- `whereBetween`
- `whereNull`
- `whereNotNull`
- `fields`
- `filters`
- `scope`
- `has`
- `whereHas`
- `orWhereHas`
- `whereDoesntHave`
- `orWhereDoesntHave`
- `with`
- `order`

## First-class operators

The request-query layer supports semantic operators in `where`, `orWhere`, and nested relation clauses such as `whereHas` and `whereDoesntHave`:

- `exact` maps to `=`
- `partial` maps to `like` with `%value%`
- `beginsWith` maps to `like` with `value%`
- `endsWith` maps to `like` with `%value`
- `eq` maps to `=`
- `neq` maps to `!=`
- `gt` maps to `>`
- `gte` maps to `>=`
- `lt` maps to `<`
- `lte` maps to `<=`
- `like` maps to `like`

These map to standard Eloquent query behavior so callers do not need to build `%value%` patterns manually.

Strict mode rejects unsupported request operators with `InvalidRequestQueryException`. Non-strict mode preserves compatibility with Laravel-supported raw operators.

## Controller example

```php
$users = $repository->fromRequest($request)->paginate(15);
$safeUsers = $repository->paginateFromRequest($request);
```

## Allowlists and strict mode

Use allowlists when the repository is exposed through HTTP APIs and you want a clear query contract.

```php
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Jooservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use Jooservices\LaravelRepository\Contracts\ProvidesRequestFiltersInterface;
use Jooservices\LaravelRepository\Contracts\ProvidesRequestQueryMetadataInterface;
use Jooservices\LaravelRepository\Contracts\RepositoryInterface;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use Jooservices\LaravelRepository\Traits\HasCrud;
use Jooservices\LaravelRepository\Traits\HasFilter;
use Jooservices\LaravelRepository\Traits\HasOrder;
use Jooservices\LaravelRepository\Traits\HasRequestFilters;
use Jooservices\LaravelRepository\Traits\HasRequestQueryMetadata;
use Jooservices\LaravelRepository\Traits\HasRequestQuery;

final class UserRepository extends EloquentRepository implements AllowsRequestQueryInterface, ProvidesRequestFiltersInterface, ProvidesRequestQueryMetadataInterface, RepositoryInterface
{
	use HasAllowedRequestQuery;
	use HasCrud;
	use HasFilter;
	use HasOrder;
	use HasRequestFilters;
	use HasRequestQueryMetadata;
	use HasRequestQuery;

	protected ?array $allowedFilters = ['status', 'name'];

	protected ?array $allowedSorts = ['name', 'created_at'];

	protected ?array $allowedIncludes = ['profile', 'posts'];

	protected ?array $allowedFields = ['id', 'name', 'email'];

	protected ?array $allowedScopes = ['active', 'email_domain'];

	protected ?array $allowedRelationFilters = [
		'posts' => ['status', 'title'],
		'posts.user' => ['email'],
	];

	protected array $filterAliases = [
		'contact' => 'email',
	];

	protected array $relationAliases = [
		'account' => 'profile',
	];

	protected array $scopeMetadata = [
		'domain' => ['scope' => 'email_domain', 'parameters' => 1],
	];

	protected array $aggregateIncludes = [
		'postsVotesSum' => ['relation' => 'posts', 'column' => 'votes', 'function' => 'sum'],
		'postsVotesAvg' => ['relation' => 'posts', 'column' => 'votes', 'function' => 'avg'],
	];

	protected array $valueRules = [
		'filters' => [
			'contact' => ['trim', 'lowercase'],
		],
		'namedFilters' => [
			'search' => ['trim', 'lowercase'],
		],
		'scopes' => [
			'domain' => ['trim', 'lowercase'],
		],
		'relations' => [
			'posts' => [
				'status' => ['trim', 'lowercase'],
			],
		],
	];

	protected array $requestFilters = [
		'search' => SearchUsersRequestFilter::class,
		'published' => static function (Builder $query, mixed $value): void {
			if ((bool) $value) {
				$query->whereHas('posts', static function (Builder $posts): void {
					$posts->where('status', 'published');
				});
			}
		},
	];

	protected ?bool $requestQueryStrict = true;

	public function __construct(User $model)
	{
		parent::__construct($model);
	}
}
```

### What happens

- disallowed filters, sorts, includes, fields, scopes, relation filters, relation count clauses, or named request filters are skipped in permissive mode
- disallowed filters, sorts, includes, fields, scopes, relation filters, relation count clauses, or named request filters throw `InvalidRequestQueryException` in strict mode
- strict mode requires request-controlled names to be present in the matching allowlist
- unsupported clause families, invalid array-only clause shapes, and unknown eager-load relations also throw `InvalidRequestQueryException` in strict mode
- scope definitions can alias public request names to model scopes and enforce exact parameter counts
- relation aliases can be reused across eager-loading includes, relation count clauses, and relation filters
- aggregate include helpers stay opt-in and are exposed through explicit request-query metadata
- value rules normalize filter values, named filter values, scope parameters, and relation-filter values before they hit the query builder
- repositories that do not opt in keep the current unrestricted behavior

## Fields, named filters, scopes, relation counts, and relation filters

```php
$request = Request::create('/', 'GET', [
	'filter' => [
		'fields' => ['name', 'email'],
		'filters' => [
			'search' => 'john',
		],
		'where' => [
			['column' => 'name', 'operator' => 'partial', 'value' => 'john'],
		],
		'scope' => [
			['name' => 'active'],
			['name' => 'domain', 'parameters' => ['example.com']],
		],
		'has' => [
			['relation' => 'posts', 'operator' => '>=', 'count' => 2],
		],
		'whereHas' => [
			[
				'relation' => 'posts.user',
				'where' => [
					['column' => 'email', 'operator' => 'exact', 'value' => 'author@example.com'],
				],
			],
		],
		'whereDoesntHave' => [
			[
				'relation' => 'posts',
				'where' => [
					['column' => 'status', 'operator' => 'exact', 'value' => 'archived'],
				],
			],
		],
		'with' => ['account', 'postsCount', 'postsExists', 'postsVotesSum'],
		'order' => [
			['column' => 'id', 'direction' => 'asc'],
		],
	],
]);

$users = $repository->fromRequest($request)->get();
```

`fields` limits the selected columns on the root model and always keeps the model key column so hydrated models remain usable.

`filters` maps request keys to dedicated filter classes or lightweight callbacks. This is useful for domain filters like `search`, `owned_by_me`, or `published` without forcing callers to build low-level `where` arrays.

`has` adds relation-count constraints such as `posts >= 2` without dropping down to a raw builder.

`whereHas`, `orWhereHas`, `whereDoesntHave`, and `orWhereDoesntHave` all use the same allowlisted relation-path and relation-column guard rails. The difference is only the relation existence operator applied on the root query.

`with` can now request regular relations plus derived `Count` and `Exists` helpers for the same public relation names, along with metadata-defined aggregate helpers such as sums or averages.

`filterAliases` lets repositories expose stable request names such as `contact` while internally targeting a different column such as `email`.

`scopeMetadata` lets repositories expose public scope names such as `domain` while internally calling a model scope such as `email_domain`.

`aggregateIncludes` lets repositories expose public request include names such as `postsVotesSum` while internally calling aggregate eager-load helpers on the real relation.

`valueRules` lets repositories normalize public request values before they are applied. Common rules include trimming, lowercasing, integer casting, boolean casting, comma-list expansion, and null handling.

## Safe Request Pagination

`paginateFromRequest($request)` applies request query clauses and reads `per_page` from the request. It uses `laravel-repository.default_per_page` when `per_page` is missing or invalid in permissive mode, and caps large values at `laravel-repository.max_per_page`.

```php
// config/laravel-repository.php
'default_per_page' => 15,
'max_per_page' => 100,
```

In strict mode, invalid `per_page` values and values above `max_per_page` throw `InvalidRequestQueryException`.

## Important boundary

The docs should not claim support for request-query clauses beyond those parsed by `RequestQueryParser` and covered by tests.
