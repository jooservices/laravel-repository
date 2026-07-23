<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Stubs;

use Jooservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use Jooservices\LaravelRepository\Contracts\RepositoryInterface;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use Jooservices\LaravelRepository\Traits\HasCrud;
use Jooservices\LaravelRepository\Traits\HasFilter;
use Jooservices\LaravelRepository\Traits\HasOrder;
use Jooservices\LaravelRepository\Traits\HasRead;
use Jooservices\LaravelRepository\Traits\HasRequestQuery;

class PostRepositoryStub extends EloquentRepository implements AllowsRequestQueryInterface, RepositoryInterface
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

    public function withStrictMode(bool $strict): static
    {
        $this->requestQueryStrict = $strict;

        return $this;
    }
}
