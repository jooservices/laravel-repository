<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Stubs;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Jooservices\LaravelRepository\Contracts\AllowsRequestQueryInterface as ARQ;
use Jooservices\LaravelRepository\Contracts\CacheableRepositoryInterface as CRI;
use Jooservices\LaravelRepository\Contracts\CriteriaRepositoryInterface as CR;
use Jooservices\LaravelRepository\Contracts\CursorPaginateableRepositoryInterface as CPR;
use Jooservices\LaravelRepository\Contracts\IteratesRepositoryInterface as IR;
use Jooservices\LaravelRepository\Contracts\ProvidesRequestFiltersInterface as PRF;
use Jooservices\LaravelRepository\Contracts\ProvidesRequestQueryMetadataInterface as PRQM;
use Jooservices\LaravelRepository\Contracts\RepositoryInterface as RR;
use Jooservices\LaravelRepository\Contracts\RequestFilterInterface as RF;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use Jooservices\LaravelRepository\Traits\HasCache;
use Jooservices\LaravelRepository\Traits\HasCriteria;
use Jooservices\LaravelRepository\Traits\HasCrud;
use Jooservices\LaravelRepository\Traits\HasCursorPagination;
use Jooservices\LaravelRepository\Traits\HasFilter;
use Jooservices\LaravelRepository\Traits\HasIteration;
use Jooservices\LaravelRepository\Traits\HasOrder;
use Jooservices\LaravelRepository\Traits\HasRead;
use Jooservices\LaravelRepository\Traits\HasRequestFilters;
use Jooservices\LaravelRepository\Traits\HasRequestQuery;
use Jooservices\LaravelRepository\Traits\HasRequestQueryMetadata;

class AllowedUserRepositoryStub extends EloquentRepository implements ARQ, CPR, CR, CRI, IR, PRF, PRQM, RR
{
    use HasAllowedRequestQuery;
    use HasCache;
    use HasCriteria;
    use HasCrud;
    use HasCursorPagination;
    use HasFilter;
    use HasIteration;
    use HasOrder;
    use HasRead;
    use HasRequestFilters;
    use HasRequestQuery;
    use HasRequestQueryMetadata;

    /**
     * @param  list<string>|null  $allowedFilters
     * @param  list<string>|null  $allowedSorts
     * @param  list<string>|null  $allowedIncludes
     * @param  list<string>|null  $allowedScopes
     * @param  array<string, list<string>>|null  $allowedRelationFilters
     */
    public function __construct(
        UserStub $model,
        ?array $allowedFilters = null,
        ?array $allowedSorts = null,
        ?array $allowedIncludes = null,
        ?bool $strict = null,
        ?array $allowedScopes = null,
        ?array $allowedRelationFilters = null,
    ) {
        parent::__construct($model);

        $this->allowedFilters = $allowedFilters;
        $this->allowedSorts = $allowedSorts;
        $this->allowedIncludes = $allowedIncludes;
        $this->allowedScopes = $allowedScopes;
        $this->relationFilters = $allowedRelationFilters;
        $this->requestQueryStrict = $strict;
    }

    /**
     * @param  list<string>|null  $allowedFields
     */
    public function withAllowedFields(?array $allowedFields): static
    {
        $this->allowedFields = $allowedFields;

        return $this;
    }

    /**
     * @param  array<string, RF|class-string<RF>|Closure(Builder<*>, mixed): void>  $requestFilters
     */
    public function withRequestFilters(array $requestFilters): static
    {
        $this->requestFilters = $requestFilters;

        return $this;
    }

    /**
     * @param  array<string, string>  $filterAliases
     */
    public function withFilterAliases(array $filterAliases): static
    {
        $this->filterAliases = $filterAliases;

        return $this;
    }

    /**
     * @param  array<string, string>  $relationAliases
     */
    public function withRelationAliases(array $relationAliases): static
    {
        $this->relationAliases = $relationAliases;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $scopeMetadata
     */
    public function withScopeMetadata(array $scopeMetadata): static
    {
        $this->scopeMetadata = $scopeMetadata;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $aggregateIncludes
     */
    public function withAggregateIncludes(array $aggregateIncludes): static
    {
        $this->aggregateIncludes = $aggregateIncludes;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $valueRules
     */
    public function withValueRules(array $valueRules): static
    {
        $this->valueRules = $valueRules;

        return $this;
    }
}
