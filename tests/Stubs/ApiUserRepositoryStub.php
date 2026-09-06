<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use JOOservices\LaravelRepository\Repositories\Presets\ApiRepository;

/**
 * @extends ApiRepository<UserStub>
 */
class ApiUserRepositoryStub extends ApiRepository
{
    public function __construct(UserStub $model)
    {
        parent::__construct($model);
    }
}
