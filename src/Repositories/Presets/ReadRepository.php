<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Repositories\Presets;

use Illuminate\Database\Eloquent\Model;
use JOOservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use JOOservices\LaravelRepository\Contracts\ReadRepositoryInterface;
use JOOservices\LaravelRepository\Repositories\EloquentRepository;
use JOOservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use JOOservices\LaravelRepository\Traits\HasDebug;
use JOOservices\LaravelRepository\Traits\HasFilter;
use JOOservices\LaravelRepository\Traits\HasOrder;
use JOOservices\LaravelRepository\Traits\HasQueryProfiles;
use JOOservices\LaravelRepository\Traits\HasRead;
use JOOservices\LaravelRepository\Traits\HasRequestQuery;

/**
 * Opt-in read-only repository preset (no create/update/delete).
 *
 * Implements {@see ReadRepositoryInterface}, not {@see \JOOservices\LaravelRepository\Contracts\RepositoryInterface}.
 *
 * @template TModel of Model
 *
 * @method TModel getModel()
 * @param  TModel  $model
 */
abstract class ReadRepository extends EloquentRepository implements
    ReadRepositoryInterface,
    AllowsRequestQueryInterface
{
    use HasAllowedRequestQuery;
    use HasDebug;
    use HasFilter;
    use HasOrder;
    use HasQueryProfiles;
    use HasRead;
    use HasRequestQuery;
}
