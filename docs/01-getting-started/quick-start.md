# Quick Start

```php
use App\Models\User;
use Jooservices\LaravelRepository\Contracts\RepositoryInterface;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasCrud;
use Jooservices\LaravelRepository\Traits\HasFilter;
use Jooservices\LaravelRepository\Traits\HasOrder;

final class UserRepository extends EloquentRepository implements RepositoryInterface
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

Typical usage:

```php
$user = $repository->find($id);
$users = $repository->filter(['status' => 'active'])->orderBy(['created_at' => 'desc'])->paginate(15);
```

When request-driven querying is needed, add `HasRequestQuery` and call `fromRequest($request)` before `get()` or `paginate()`.

Use `paginateFromRequest($request)` when the endpoint should accept a guarded `per_page` request value using the package `default_per_page` and `max_per_page` config.
