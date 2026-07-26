<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use JOOservices\LaravelRepository\Contracts\RepositoryInterface;
use JOOservices\LaravelRepository\Repositories\EloquentRepository;
use JOOservices\LaravelRepository\Traits\HasCrud;
use JOOservices\LaravelRepository\Traits\HasFilter;
use JOOservices\LaravelRepository\Traits\HasOrder;
use JOOservices\LaravelRepository\Traits\HasRead;
use JOOservices\LaravelRepository\Traits\HasRequestQuery;

class UserRepositoryStub extends EloquentRepository implements RepositoryInterface
{
    use HasCrud;
    use HasFilter;
    use HasOrder;
    use HasRead;
    use HasRequestQuery;
}
