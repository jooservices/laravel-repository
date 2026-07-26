<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use JOOservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use JOOservices\LaravelRepository\Contracts\RepositoryInterface;
use JOOservices\LaravelRepository\Repositories\EloquentRepository;
use JOOservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use JOOservices\LaravelRepository\Traits\HasCrud;
use JOOservices\LaravelRepository\Traits\HasFilter;
use JOOservices\LaravelRepository\Traits\HasOrder;
use JOOservices\LaravelRepository\Traits\HasRead;
use JOOservices\LaravelRepository\Traits\HasRequestQuery;

class CommentRepositoryStub extends EloquentRepository implements AllowsRequestQueryInterface, RepositoryInterface
{
    use HasAllowedRequestQuery;
    use HasCrud;
    use HasFilter;
    use HasOrder;
    use HasRead;
    use HasRequestQuery;

    /**
     * @param  list<string>|null  $allowedFields
     */
    public function withAllowedFields(?array $allowedFields): static
    {
        $this->allowedFields = $allowedFields;

        return $this;
    }

    /**
     * @param  list<string>|null  $allowedIncludes
     */
    public function withAllowedIncludes(?array $allowedIncludes): static
    {
        $this->allowedIncludes = $allowedIncludes;

        return $this;
    }
}
