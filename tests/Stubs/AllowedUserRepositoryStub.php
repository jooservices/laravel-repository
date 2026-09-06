<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use JOOservices\LaravelRepository\Contracts\AllowsRequestQueryInterface as ARQ;
use JOOservices\LaravelRepository\Contracts\CacheableRepositoryInterface as CRI;
use JOOservices\LaravelRepository\Contracts\CriteriaRepositoryInterface as CR;
use JOOservices\LaravelRepository\Contracts\CursorPaginateableRepositoryInterface as CPR;
use JOOservices\LaravelRepository\Contracts\IteratesRepositoryInterface as IR;
use JOOservices\LaravelRepository\Contracts\ProvidesRequestFiltersInterface as PRF;
use JOOservices\LaravelRepository\Contracts\ProvidesRequestQueryMetadataInterface as PRQM;
use JOOservices\LaravelRepository\Contracts\RepositoryInterface as RR;
use JOOservices\LaravelRepository\Contracts\RequestFilterInterface as RF;
use JOOservices\LaravelRepository\Repositories\EloquentRepository;
use JOOservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use JOOservices\LaravelRepository\Traits\HasCache;
use JOOservices\LaravelRepository\Traits\HasCriteria;
use JOOservices\LaravelRepository\Traits\HasCrud;
use JOOservices\LaravelRepository\Traits\HasCursorPagination;
use JOOservices\LaravelRepository\Traits\HasFilter;
use JOOservices\LaravelRepository\Traits\HasIteration;
use JOOservices\LaravelRepository\Traits\HasOrder;
use JOOservices\LaravelRepository\Traits\HasRead;
use JOOservices\LaravelRepository\Traits\HasRequestFilters;
use JOOservices\LaravelRepository\Traits\HasRequestQuery;
use JOOservices\LaravelRepository\Traits\HasRequestQueryMetadata;

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

    /**
     * @param  list<string>  $trustedRelations
     */
    public function withTrustedRelations(array $trustedRelations): static
    {
        $this->trustedRelations = array_values(array_filter(
            $trustedRelations,
            static fn(mixed $value): bool => is_string($value) && $value !== '',
        ));

        return $this;
    }
}
