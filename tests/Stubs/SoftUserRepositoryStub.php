<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use JOOservices\LaravelRepository\Repositories\EloquentRepository;
use JOOservices\LaravelRepository\Traits\HasCrud;
use JOOservices\LaravelRepository\Traits\HasFilter;
use JOOservices\LaravelRepository\Traits\HasLocking;
use JOOservices\LaravelRepository\Traits\HasRead;
use JOOservices\LaravelRepository\Traits\HasSoftDeletes;

class SoftUserRepositoryStub extends EloquentRepository
{
    use HasCrud;
    use HasFilter;
    use HasLocking;
    use HasRead;
    use HasSoftDeletes;
}
