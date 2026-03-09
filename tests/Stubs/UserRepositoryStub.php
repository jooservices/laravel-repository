<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Stubs;

use Jooservices\LaravelRepository\Contracts\RepositoryInterface;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasCrud;
use Jooservices\LaravelRepository\Traits\HasFilter;
use Jooservices\LaravelRepository\Traits\HasOrder;
use Jooservices\LaravelRepository\Traits\HasRequestQuery;

class UserRepositoryStub extends EloquentRepository implements RepositoryInterface
{
    use HasCrud;
    use HasFilter;
    use HasOrder;
    use HasRequestQuery;

    public function __construct(UserStub $model)
    {
        parent::__construct($model);
    }
}
