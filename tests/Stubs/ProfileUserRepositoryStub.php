<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use JOOservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use JOOservices\LaravelRepository\Repositories\EloquentRepository;
use JOOservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use JOOservices\LaravelRepository\Traits\HasCrud;
use JOOservices\LaravelRepository\Traits\HasFilter;
use JOOservices\LaravelRepository\Traits\HasQueryProfiles;
use JOOservices\LaravelRepository\Traits\HasRequestQuery;

class ProfileUserRepositoryStub extends EloquentRepository implements AllowsRequestQueryInterface
{
    use HasAllowedRequestQuery;
    use HasCrud;
    use HasFilter;
    use HasQueryProfiles;
    use HasRequestQuery;

    /**
     * @param  list<string>|null  $allowedIncludes
     */
    public function withAllowedIncludes(?array $allowedIncludes): static
    {
        $this->allowedIncludes = $allowedIncludes;

        return $this;
    }

    public function withRequestQueryStrict(bool $strict): static
    {
        $this->requestQueryStrict = $strict;

        return $this;
    }
}
