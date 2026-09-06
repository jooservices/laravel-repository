<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Repositories\Presets;

use Illuminate\Database\Eloquent\Model;
use JOOservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use JOOservices\LaravelRepository\Contracts\CrudRepositoryInterface;
use JOOservices\LaravelRepository\Contracts\FilterableRepositoryInterface;
use JOOservices\LaravelRepository\Contracts\OrderableRepositoryInterface;
use JOOservices\LaravelRepository\Contracts\ReadableRepositoryInterface;
use JOOservices\LaravelRepository\Contracts\RepositoryInterface;
use JOOservices\LaravelRepository\Contracts\RequestQueryRepositoryInterface;
use JOOservices\LaravelRepository\Repositories\EloquentRepository;
use JOOservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use JOOservices\LaravelRepository\Traits\HasCrud;
use JOOservices\LaravelRepository\Traits\HasDebug;
use JOOservices\LaravelRepository\Traits\HasFilter;
use JOOservices\LaravelRepository\Traits\HasOrder;
use JOOservices\LaravelRepository\Traits\HasQueryProfiles;
use JOOservices\LaravelRepository\Traits\HasRead;
use JOOservices\LaravelRepository\Traits\HasRequestQuery;

/**
 * Opt-in API-oriented repository preset (CRUD + filter/order/read + request query).
 *
 * @template TModel of Model
 *
 * @method TModel getModel()
 * @param  TModel  $model
 */
abstract class ApiRepository extends EloquentRepository implements
    RepositoryInterface,
    CrudRepositoryInterface,
    FilterableRepositoryInterface,
    OrderableRepositoryInterface,
    ReadableRepositoryInterface,
    RequestQueryRepositoryInterface,
    AllowsRequestQueryInterface
{
    use HasAllowedRequestQuery;
    use HasCrud;
    use HasDebug;
    use HasFilter;
    use HasOrder;
    use HasQueryProfiles;
    use HasRead;
    use HasRequestQuery;
}
