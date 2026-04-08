<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jooservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use Jooservices\LaravelRepository\Contracts\ProvidesRequestFiltersInterface;
use Jooservices\LaravelRepository\Contracts\ProvidesRequestQueryMetadataInterface;
use Jooservices\LaravelRepository\Contracts\RequestFilterInterface;
use Jooservices\LaravelRepository\Exceptions\InvalidRequestQueryException;
use Jooservices\LaravelRepository\Support\QueryOperator;
use Jooservices\LaravelRepository\Support\RequestQueryParser;
use Jooservices\LaravelRepository\Support\RequestQueryValueNormalizer;
use LogicException;

trait HasRequestQuery
{
    /**
     * @var list<string>
     */
    private const REQUEST_QUERY_CLAUSES = [
        'where',
        'orWhere',
        'whereIn',
        'whereBetween',
        'whereNull',
        'whereNotNull',
        'fields',
        'filters',
        'scope',
        'has',
        'whereHas',
        'orWhereHas',
        'whereDoesntHave',
        'orWhereDoesntHave',
        'with',
        'order',
    ];

    /**
     * @var list<string>
     */
    private const ARRAY_ONLY_REQUEST_QUERY_CLAUSES = [
        'where',
        'orWhere',
        'whereIn',
        'whereBetween',
        'whereNull',
        'whereNotNull',
        'filters',
        'has',
        'whereHas',
        'orWhereHas',
        'whereDoesntHave',
        'orWhereDoesntHave',
        'order',
    ];

    public function fromRequest(Request $request): static
    {
        $data = $this->requestQueryData($request);
        $this->assertSupportedRequestQuery($data);

        $clauses = RequestQueryParser::parse($data);
        $query = $this->getQuery();
        $filterValueResolver = fn (string $column, mixed $value): mixed => $this->normalizeFilterValue($column, $value);

        $this->applyFieldClauses($query, $clauses['fields']);
        $this->applyNamedFilterClauses($query, $clauses['filters']);
        $this->applyWhereClauses($query, $clauses['where'], null, $filterValueResolver);
        $this->applyOrWhereClauses($query, $clauses['orWhere'], null, $filterValueResolver);
        $this->applyWhereInClauses($query, $clauses['whereIn'], null, $filterValueResolver);
        $this->applyWhereBetweenClauses($query, $clauses['whereBetween'], null, $filterValueResolver);
        $this->applyWhereNullClauses($query, $clauses['whereNull']);
        $this->applyWhereNotNullClauses($query, $clauses['whereNotNull']);
        $this->applyScopeClauses($query, $clauses['scope']);
        $this->applyHasClauses($query, $clauses['has']);
        $this->applyWhereHasClauses($query, $clauses['whereHas']);
        $this->applyOrWhereHasClauses($query, $clauses['orWhereHas']);
        $this->applyWhereDoesntHaveClauses($query, $clauses['whereDoesntHave']);
        $this->applyOrWhereDoesntHaveClauses($query, $clauses['orWhereDoesntHave']);
        $this->applyIncludeClauses($query, $clauses['with']);
        $this->applyOrderClauses($query, $clauses['order']);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestQueryData(Request $request): array
    {
        $data = $request->input('filter') ?? $request->input('query') ?? [];

        if (! is_array($data)) {
            if ($this->requestQueryStrictMode()) {
                throw InvalidRequestQueryException::invalidClause();
            }

            return [];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertSupportedRequestQuery(array $data): void
    {
        if (! $this->requestQueryStrictMode()) {
            return;
        }

        foreach ($data as $clause => $value) {
            if (! is_string($clause)) {
                continue;
            }

            if (! in_array($clause, self::REQUEST_QUERY_CLAUSES, true)) {
                throw InvalidRequestQueryException::unsupportedClause($clause, self::REQUEST_QUERY_CLAUSES);
            }

            if (in_array($clause, self::ARRAY_ONLY_REQUEST_QUERY_CLAUSES, true) && ! is_array($value)) {
                throw InvalidRequestQueryException::invalidClause($clause);
            }
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $clauses
     */
    private function applyFieldClauses(Builder $query, array $clauses): void
    {
        if ($clauses === []) {
            return;
        }

        $model = $this->getModel();
        $selected = [];

        foreach ($clauses as $field) {
            if (! $this->shouldApplyField($field)) {
                continue;
            }

            $selected[] = str_contains($field, '.') ? $field : $model->qualifyColumn($field);
        }

        if ($selected === []) {
            return;
        }

        $keyColumn = $model->qualifyColumn($model->getKeyName());
        if (! in_array($keyColumn, $selected, true)) {
            array_unshift($selected, $keyColumn);
        }

        $query->select(array_values(array_unique($selected)));
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<string, mixed>  $clauses
     */
    private function applyNamedFilterClauses(Builder $query, array $clauses): void
    {
        foreach ($clauses as $name => $value) {
            $filter = $this->resolveRequestFilter($name);
            if ($filter === null) {
                continue;
            }

            $value = $this->normalizeNamedFilterValue($name, $value);

            if ($filter instanceof RequestFilterInterface) {
                $filter->apply($query, $value);

                continue;
            }

            $filter($query, $value);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, operator: string, value: mixed}>  $clauses
     * @param  (callable(string): bool)|null  $guard
     * @param  (callable(string, mixed): mixed)|null  $valueResolver
     */
    private function applyWhereClauses(
        Builder $query,
        array $clauses,
        ?callable $guard = null,
        ?callable $valueResolver = null,
    ): void {
        foreach ($clauses as $where) {
            $column = $this->resolveRequestedFilterColumn($where['column'], $guard);
            if ($column === null) {
                continue;
            }

            $value = $valueResolver
                ? $valueResolver($where['column'], $where['value'])
                : $where['value'];

            QueryOperator::apply($query, 'where', $column, $where['operator'], $value);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, operator: string, value: mixed}>  $clauses
     * @param  (callable(string): bool)|null  $guard
     * @param  (callable(string, mixed): mixed)|null  $valueResolver
     */
    private function applyOrWhereClauses(
        Builder $query,
        array $clauses,
        ?callable $guard = null,
        ?callable $valueResolver = null,
    ): void {
        foreach ($clauses as $where) {
            $column = $this->resolveRequestedFilterColumn($where['column'], $guard);
            if ($column === null) {
                continue;
            }

            $value = $valueResolver
                ? $valueResolver($where['column'], $where['value'])
                : $where['value'];

            QueryOperator::apply($query, 'orWhere', $column, $where['operator'], $value);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, values: list<mixed>}>  $clauses
     * @param  (callable(string): bool)|null  $guard
     * @param  (callable(string, mixed): mixed)|null  $valueResolver
     */
    private function applyWhereInClauses(
        Builder $query,
        array $clauses,
        ?callable $guard = null,
        ?callable $valueResolver = null,
    ): void {
        foreach ($clauses as $whereIn) {
            $column = $this->resolveRequestedFilterColumn($whereIn['column'], $guard);
            if ($column === null) {
                continue;
            }

            $values = $valueResolver
                ? $valueResolver($whereIn['column'], $whereIn['values'])
                : $whereIn['values'];
            $query->whereIn($column, is_array($values) ? $values : [$values]);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, range: list<mixed>}>  $clauses
     * @param  (callable(string): bool)|null  $guard
     * @param  (callable(string, mixed): mixed)|null  $valueResolver
     */
    private function applyWhereBetweenClauses(
        Builder $query,
        array $clauses,
        ?callable $guard = null,
        ?callable $valueResolver = null,
    ): void {
        foreach ($clauses as $whereBetween) {
            $column = $this->resolveRequestedFilterColumn($whereBetween['column'], $guard);
            if ($column === null) {
                continue;
            }

            $range = $valueResolver
                ? $valueResolver($whereBetween['column'], $whereBetween['range'])
                : $whereBetween['range'];
            $range = is_array($range) ? array_values($range) : [];

            if (count($range) >= 2) {
                $query->whereBetween($column, $range);
            }
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $clauses
     * @param  (callable(string): bool)|null  $guard
     */
    private function applyWhereNullClauses(Builder $query, array $clauses, ?callable $guard = null): void
    {
        foreach ($clauses as $column) {
            $resolvedColumn = $this->resolveRequestedFilterColumn($column, $guard);
            if ($resolvedColumn === null) {
                continue;
            }

            $query->whereNull($resolvedColumn);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $clauses
     * @param  (callable(string): bool)|null  $guard
     */
    private function applyWhereNotNullClauses(Builder $query, array $clauses, ?callable $guard = null): void
    {
        foreach ($clauses as $column) {
            $resolvedColumn = $this->resolveRequestedFilterColumn($column, $guard);
            if ($resolvedColumn === null) {
                continue;
            }

            $query->whereNotNull($resolvedColumn);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $clauses
     */
    private function applyIncludeClauses(Builder $query, array $clauses): void
    {
        foreach ($clauses as $requestedInclude) {
            $include = $this->resolveIncludeRequest($requestedInclude);
            if ($include === null) {
                continue;
            }

            $relation = $include['relation'];

            if (! $this->relationExists($relation)) {
                if ($this->requestQueryStrictMode()) {
                    throw InvalidRequestQueryException::unknownTarget('Relation', $relation);
                }

                continue;
            }

            match ($include['type']) {
                'relation' => $query->with($relation),
                'count' => $query->withCount([$relation.' as '.$include['attribute']]),
                'exists' => $query->withExists([$relation.' as '.$include['attribute']]),
                'sum' => $query->withSum($relation.' as '.$include['attribute'], $include['column']),
                'avg' => $query->withAvg($relation.' as '.$include['attribute'], $include['column']),
                'min' => $query->withMin($relation.' as '.$include['attribute'], $include['column']),
                'max' => $query->withMax($relation.' as '.$include['attribute'], $include['column']),
                default => throw new LogicException(sprintf('Unsupported include type [%s].', $include['type'])),
            };
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{name: string, parameters: list<mixed>}>  $clauses
     */
    private function applyScopeClauses(Builder $query, array $clauses): void
    {
        foreach ($clauses as $scope) {
            $parameters = $this->normalizeScopeParameters($scope['name'], $scope['parameters']);
            $definition = $this->resolveScopeClause($scope['name'], $parameters);
            if ($definition === null) {
                continue;
            }

            if (! $this->scopeExists($definition['scope'])) {
                if ($this->requestQueryStrictMode()) {
                    throw InvalidRequestQueryException::unknownTarget('Scope', $definition['scope']);
                }

                continue;
            }

            $query->{Str::camel($definition['scope'])}(...$definition['parameters']);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{relation: string, operator: string, count: int}>  $clauses
     */
    private function applyHasClauses(Builder $query, array $clauses): void
    {
        foreach ($clauses as $clause) {
            $requestedRelation = $clause['relation'];
            $relation = $this->resolveRelationAlias($requestedRelation);

            if (! $this->shouldApplyRelationCount($requestedRelation)) {
                continue;
            }

            if (! $this->relationExists($relation)) {
                if ($this->requestQueryStrictMode()) {
                    throw InvalidRequestQueryException::unknownTarget('Relation', $relation);
                }

                continue;
            }

            $query->has($relation, $clause['operator'], $clause['count']);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{
     *     relation: string,
     *     where: list<array{column: string, operator: string, value: mixed}>,
     *     orWhere: list<array{column: string, operator: string, value: mixed}>,
     *     whereIn: list<array{column: string, values: list<mixed>}>,
     *     whereBetween: list<array{column: string, range: list<mixed>}>,
     *     whereNull: list<string>,
     *     whereNotNull: list<string>
     * }>  $clauses
     */
    private function applyWhereHasClauses(Builder $query, array $clauses): void
    {
        $this->applyRelationClauses($query, $clauses, 'whereHas');
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{
     *     relation: string,
     *     where: list<array{column: string, operator: string, value: mixed}>,
     *     orWhere: list<array{column: string, operator: string, value: mixed}>,
     *     whereIn: list<array{column: string, values: list<mixed>}>,
     *     whereBetween: list<array{column: string, range: list<mixed>}>,
     *     whereNull: list<string>,
     *     whereNotNull: list<string>
     * }>  $clauses
     */
    private function applyOrWhereHasClauses(Builder $query, array $clauses): void
    {
        $this->applyRelationClauses($query, $clauses, 'orWhereHas');
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{
     *     relation: string,
     *     where: list<array{column: string, operator: string, value: mixed}>,
     *     orWhere: list<array{column: string, operator: string, value: mixed}>,
     *     whereIn: list<array{column: string, values: list<mixed>}>,
     *     whereBetween: list<array{column: string, range: list<mixed>}>,
     *     whereNull: list<string>,
     *     whereNotNull: list<string>
     * }>  $clauses
     */
    private function applyWhereDoesntHaveClauses(Builder $query, array $clauses): void
    {
        $this->applyRelationClauses($query, $clauses, 'whereDoesntHave');
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{
     *     relation: string,
     *     where: list<array{column: string, operator: string, value: mixed}>,
     *     orWhere: list<array{column: string, operator: string, value: mixed}>,
     *     whereIn: list<array{column: string, values: list<mixed>}>,
     *     whereBetween: list<array{column: string, range: list<mixed>}>,
     *     whereNull: list<string>,
     *     whereNotNull: list<string>
     * }>  $clauses
     */
    private function applyOrWhereDoesntHaveClauses(Builder $query, array $clauses): void
    {
        $this->applyRelationClauses($query, $clauses, 'orWhereDoesntHave');
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{
     *     relation: string,
     *     where: list<array{column: string, operator: string, value: mixed}>,
     *     orWhere: list<array{column: string, operator: string, value: mixed}>,
     *     whereIn: list<array{column: string, values: list<mixed>}>,
     *     whereBetween: list<array{column: string, range: list<mixed>}>,
     *     whereNull: list<string>,
     *     whereNotNull: list<string>
     * }>  $clauses
     */
    private function applyRelationClauses(Builder $query, array $clauses, string $method): void
    {
        foreach ($clauses as $clause) {
            $requestedRelation = $clause['relation'];
            $relation = $this->resolveRelationAlias($requestedRelation);

            if (! $this->shouldApplyRelationFilter($requestedRelation)) {
                continue;
            }

            if (! $this->relationExists($relation)) {
                if ($this->requestQueryStrictMode()) {
                    throw InvalidRequestQueryException::unknownTarget('Relation', $relation);
                }

                continue;
            }

            $callback = function (Builder $relationQuery) use ($clause, $relation, $requestedRelation): void {
                $guard = fn (string $column): bool => $this->shouldApplyRelationColumn($relation, $column);
                $valueResolver = fn (string $column, mixed $value): mixed => $this->normalizeRelationFilterValue(
                    $requestedRelation,
                    $column,
                    $value,
                );

                $this->applyWhereClauses($relationQuery, $clause['where'], $guard, $valueResolver);
                $this->applyOrWhereClauses($relationQuery, $clause['orWhere'], $guard, $valueResolver);
                $this->applyWhereInClauses($relationQuery, $clause['whereIn'], $guard, $valueResolver);
                $this->applyWhereBetweenClauses($relationQuery, $clause['whereBetween'], $guard, $valueResolver);
                $this->applyWhereNullClauses($relationQuery, $clause['whereNull'], $guard);
                $this->applyWhereNotNullClauses($relationQuery, $clause['whereNotNull'], $guard);
            };

            match ($method) {
                'whereHas' => $query->whereHas($relation, $callback),
                'orWhereHas' => $query->orWhereHas($relation, $callback),
                'whereDoesntHave' => $query->whereDoesntHave($relation, $callback),
                'orWhereDoesntHave' => $query->orWhereDoesntHave($relation, $callback),
                default => throw new LogicException(sprintf('Unsupported relation clause method [%s].', $method)),
            };
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, direction: string}>  $clauses
     */
    private function applyOrderClauses(Builder $query, array $clauses): void
    {
        foreach ($clauses as $order) {
            if (! $this->shouldApplySort($order['column'])) {
                continue;
            }

            $query->orderBy($order['column'], $order['direction']);
        }
    }

    private function shouldApplyFilter(string $column): bool
    {
        return $this->guardAllowedValue(
            $column,
            $this->requestQueryVisibleFilters(),
            static function (string $value, array $allowed): InvalidRequestQueryException {
                return InvalidRequestQueryException::disallowedFilter($value, $allowed);
            },
        );
    }

    private function shouldApplySort(string $column): bool
    {
        return $this->guardAllowedValue(
            $column,
            $this->requestQueryAllowedSorts(),
            static function (string $value, array $allowed): InvalidRequestQueryException {
                return InvalidRequestQueryException::disallowedSort($value, $allowed);
            },
        );
    }

    private function shouldApplyInclude(string $relation): bool
    {
        return $this->guardAllowedValue(
            $relation,
            $this->requestQueryVisibleIncludes(),
            static function (string $value, array $allowed): InvalidRequestQueryException {
                return InvalidRequestQueryException::disallowedInclude($value, $allowed);
            },
        );
    }

    private function shouldApplyField(string $field): bool
    {
        return $this->guardAllowedValue(
            $field,
            $this->requestQueryAllowedFields(),
            static function (string $value, array $allowed): InvalidRequestQueryException {
                return InvalidRequestQueryException::disallowedField($value, $allowed);
            },
        );
    }

    /**
     * @param  list<string>|null  $allowed
     * @param  callable(string, list<string>): InvalidRequestQueryException  $exceptionFactory
     */
    private function guardAllowedValue(string $value, ?array $allowed, callable $exceptionFactory): bool
    {
        if ($allowed === null || in_array($value, $allowed, true)) {
            return true;
        }

        if ($this->requestQueryStrictMode()) {
            throw $exceptionFactory($value, $allowed);
        }

        return false;
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryAllowedFilters(): ?array
    {
        if (! $this instanceof AllowsRequestQueryInterface) {
            return null;
        }

        return $this->allowedFilters();
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryAllowedSorts(): ?array
    {
        if (! $this instanceof AllowsRequestQueryInterface) {
            return null;
        }

        return $this->allowedSorts();
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryAllowedIncludes(): ?array
    {
        if (! $this instanceof AllowsRequestQueryInterface) {
            return null;
        }

        return $this->allowedIncludes();
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryAllowedFields(): ?array
    {
        if (! $this instanceof AllowsRequestQueryInterface) {
            return null;
        }

        return $this->allowedFields();
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryAllowedScopes(): ?array
    {
        if (! $this instanceof AllowsRequestQueryInterface) {
            return null;
        }

        return $this->allowedScopes();
    }

    /**
     * @return array<string, list<string>>|null
     */
    private function requestQueryAllowedRelationFilters(): ?array
    {
        if (! $this instanceof AllowsRequestQueryInterface) {
            return null;
        }

        return $this->allowedRelationFilters();
    }

    private function shouldApplyScope(string $scope): bool
    {
        return $this->guardAllowedValue(
            $scope,
            $this->requestQueryVisibleScopes(),
            static function (string $value, array $allowed): InvalidRequestQueryException {
                return InvalidRequestQueryException::disallowedScope($value, $allowed);
            },
        );
    }

    private function shouldApplyRelationFilter(string $relation): bool
    {
        $allowed = $this->requestQueryVisibleRelationFilters();
        if ($allowed === null || in_array($relation, $allowed, true)) {
            return true;
        }

        if ($this->requestQueryStrictMode()) {
            throw InvalidRequestQueryException::disallowedRelation($relation, $allowed);
        }

        return false;
    }

    private function shouldApplyRelationCount(string $relation): bool
    {
        $allowed = $this->requestQueryVisibleRelationCounts();
        if ($allowed === null || in_array($relation, $allowed, true)) {
            return true;
        }

        if ($this->requestQueryStrictMode()) {
            throw InvalidRequestQueryException::disallowedRelationDetail('count', $relation, $allowed);
        }

        return false;
    }

    private function shouldApplyRelationColumn(string $relation, string $column): bool
    {
        $allowed = $this->requestQueryAllowedRelationFilters();
        if ($allowed === null) {
            return true;
        }

        $columns = $allowed[$relation] ?? [];
        if (in_array($column, $columns, true)) {
            return true;
        }

        if ($this->requestQueryStrictMode()) {
            throw InvalidRequestQueryException::disallowedRelationDetail('column', $relation, $columns, $column);
        }

        return false;
    }

    /**
     * @return RequestFilterInterface|Closure(Builder<*>, mixed): void|null
     */
    private function resolveRequestFilter(string $name): RequestFilterInterface|Closure|null
    {
        $resolvedFilter = null;

        if (! $this instanceof ProvidesRequestFiltersInterface) {
            if ($this->requestQueryStrictMode()) {
                throw InvalidRequestQueryException::disallowedRequestFilter($name, []);
            }
        } else {
            $filters = $this->requestFilters();
            $filter = $filters[$name] ?? null;

            if ($filter instanceof RequestFilterInterface || $filter instanceof Closure) {
                $resolvedFilter = $filter;
            } elseif (is_string($filter) && is_subclass_of($filter, RequestFilterInterface::class)) {
                $resolved = app($filter);

                if ($resolved instanceof RequestFilterInterface) {
                    $resolvedFilter = $resolved;
                }
            }

            if ($resolvedFilter === null && $this->requestQueryStrictMode()) {
                throw InvalidRequestQueryException::disallowedRequestFilter($name, array_keys($filters));
            }
        }

        return $resolvedFilter;
    }

    /**
     * @param  (callable(string): bool)|null  $guard
     */
    private function resolveRequestedFilterColumn(string $column, ?callable $guard): ?string
    {
        if ($guard !== null) {
            return $guard($column) ? $column : null;
        }

        if (! $this->shouldApplyFilter($column)) {
            return null;
        }

        return $this->resolveFilterAlias($column);
    }

    /**
     * @param  callable(string): bool|null  $guard
     */
    private function passesColumnGuard(string $column, ?callable $guard): bool
    {
        return $guard ? $guard($column) : $this->shouldApplyFilter($column);
    }

    private function scopeExists(string $scope): bool
    {
        return method_exists($this->getModel(), 'scope'.Str::studly($scope));
    }

    /**
     * @param  list<mixed>  $parameters
     * @return array{scope: string, parameters: list<mixed>}|null
     */
    private function resolveScopeClause(string $scope, array $parameters): ?array
    {
        $metadata = $this->requestQueryScopeMetadata()[$scope] ?? null;
        $resolvedScope = $metadata['scope'] ?? $scope;

        if (! $this->shouldApplyScope($scope)) {
            return null;
        }

        $expectedParameters = $metadata['parameters'] ?? null;
        if ($expectedParameters !== null && count($parameters) !== $expectedParameters) {
            if ($this->requestQueryStrictMode()) {
                throw InvalidRequestQueryException::invalidScopeParameters(
                    $scope,
                    $expectedParameters,
                    count($parameters),
                );
            }

            return null;
        }

        return [
            'scope' => $resolvedScope,
            'parameters' => $parameters,
        ];
    }

    /**
     * @return array{type: string, relation: string, attribute: string, column?: string}|null
     */
    private function resolveIncludeRequest(string $relation): ?array
    {
        if (! $this->shouldApplyInclude($relation)) {
            return null;
        }

        $aggregate = $this->requestQueryAggregateIncludes()[$relation] ?? null;
        if ($aggregate !== null) {
            return [
                'type' => $aggregate['function'],
                'relation' => $this->resolveRelationAlias($aggregate['relation']),
                'attribute' => $aggregate['attribute'],
                'column' => $aggregate['column'],
            ];
        }

        $type = 'relation';
        $base = $relation;

        if (Str::endsWith($relation, 'Count')) {
            $type = 'count';
            $base = Str::beforeLast($relation, 'Count');
        } elseif (Str::endsWith($relation, 'Exists')) {
            $type = 'exists';
            $base = Str::beforeLast($relation, 'Exists');
        }

        return [
            'type' => $type,
            'relation' => $this->resolveRelationAlias($base),
            'attribute' => match ($type) {
                'count' => Str::snake($base).'_count',
                'exists' => Str::snake($base).'_exists',
                default => '',
            },
        ];
    }

    private function resolveFilterAlias(string $column): string
    {
        return $this->requestQueryFilterAliases()[$column] ?? $column;
    }

    private function resolveRelationAlias(string $relation): string
    {
        return $this->requestQueryRelationAliases()[$relation] ?? $relation;
    }

    /**
     * @return array<string, string>
     */
    private function requestQueryFilterAliases(): array
    {
        if (! $this instanceof ProvidesRequestQueryMetadataInterface) {
            return [];
        }

        return $this->filterAliases();
    }

    /**
     * @return array<string, string>
     */
    private function requestQueryRelationAliases(): array
    {
        if (! $this instanceof ProvidesRequestQueryMetadataInterface) {
            return [];
        }

        return $this->relationAliases();
    }

    /**
     * @return array<string, array{scope: string, parameters: int|null}>
     */
    private function requestQueryScopeMetadata(): array
    {
        if (! $this instanceof ProvidesRequestQueryMetadataInterface) {
            return [];
        }

        return $this->scopeMetadata();
    }

    /**
     * @return array<string, array{relation: string, column: string, function: string, attribute: string}>
     */
    private function requestQueryAggregateIncludes(): array
    {
        if (! $this instanceof ProvidesRequestQueryMetadataInterface) {
            return [];
        }

        return $this->aggregateIncludes();
    }

    /**
     * @return array{
     *     filters: array<string, list<mixed>>,
     *     namedFilters: array<string, list<mixed>>,
     *     scopes: array<string, list<mixed>>,
     *     relations: array<string, array<string, list<mixed>>>
     * }
     */
    private function requestQueryValueRules(): array
    {
        if (! $this instanceof ProvidesRequestQueryMetadataInterface) {
            return [
                'filters' => [],
                'namedFilters' => [],
                'scopes' => [],
                'relations' => [],
            ];
        }

        return $this->valueRules();
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryVisibleFilters(): ?array
    {
        return $this->visibleAliasedNames(
            $this->requestQueryAllowedFilters(),
            $this->requestQueryFilterAliases(),
        );
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryVisibleScopes(): ?array
    {
        $allowedScopes = $this->requestQueryAllowedScopes();
        $metadata = $this->requestQueryScopeMetadata();

        if ($allowedScopes === null) {
            return null;
        }

        $visible = $allowedScopes;
        $aliasedTargets = [];

        foreach ($metadata as $requestName => $definition) {
            if (! in_array($definition['scope'], $allowedScopes, true)) {
                continue;
            }

            $visible[] = $requestName;
            if ($requestName !== $definition['scope']) {
                $aliasedTargets[] = $definition['scope'];
            }
        }

        return array_values(array_unique(array_filter(
            $visible,
            static fn (string $name): bool => ! in_array($name, $aliasedTargets, true),
        )));
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryVisibleIncludes(): ?array
    {
        $visible = $this->visibleAliasedNames(
            $this->requestQueryAllowedIncludes(),
            $this->requestQueryRelationAliases(),
        );

        if ($visible === null) {
            return null;
        }

        $derived = [];
        foreach ($visible as $relation) {
            $derived[] = $relation.'Count';
            $derived[] = $relation.'Exists';
        }

        foreach ($this->requestQueryAggregateIncludes() as $requestName => $definition) {
            if (in_array(
                $this->resolveRelationAlias($definition['relation']),
                $this->requestQueryAllowedIncludes() ?? [],
                true,
            )) {
                $derived[] = $requestName;
            }
        }

        return array_values(array_unique([...$visible, ...$derived]));
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryVisibleRelationFilters(): ?array
    {
        $allowed = $this->requestQueryAllowedRelationFilters();
        if ($allowed === null) {
            return null;
        }

        return $this->visibleAliasedNames(array_keys($allowed), $this->requestQueryRelationAliases());
    }

    /**
     * @return list<string>|null
     */
    private function requestQueryVisibleRelationCounts(): ?array
    {
        $allowedIncludes = $this->requestQueryAllowedIncludes();
        $relationFilters = $this->requestQueryAllowedRelationFilters();

        if ($allowedIncludes === null && $relationFilters === null) {
            return null;
        }

        $relations = array_values(array_unique([
            ...($allowedIncludes ?? []),
            ...array_keys($relationFilters ?? []),
        ]));

        return $this->visibleAliasedNames($relations, $this->requestQueryRelationAliases());
    }

    /**
     * @param  list<string>|null  $allowed
     * @param  array<string, string>  $aliases
     * @return list<string>|null
     */
    private function visibleAliasedNames(?array $allowed, array $aliases): ?array
    {
        if ($allowed === null) {
            return null;
        }

        $visible = $allowed;
        $aliasedTargets = [];

        foreach ($aliases as $requestName => $target) {
            if (! in_array($target, $allowed, true)) {
                continue;
            }

            $visible[] = $requestName;
            if ($requestName !== $target) {
                $aliasedTargets[] = $target;
            }
        }

        return array_values(array_unique(array_filter(
            $visible,
            static fn (string $name): bool => ! in_array($name, $aliasedTargets, true),
        )));
    }

    private function relationExists(string $relation): bool
    {
        $model = $this->getModel();

        foreach (explode('.', $relation) as $segment) {
            if (! method_exists($model, $segment)) {
                return false;
            }

            $relationObject = $model->{$segment}();
            if (! $relationObject instanceof Relation) {
                return false;
            }

            $model = $relationObject->getRelated();
        }

        return true;
    }

    private function requestQueryStrictMode(): bool
    {
        return $this instanceof AllowsRequestQueryInterface && $this->isRequestQueryStrict();
    }

    private function normalizeFilterValue(string $column, mixed $value): mixed
    {
        $rules = $this->requestQueryValueRules()['filters'][$column] ?? [];

        return $rules === [] ? $value : RequestQueryValueNormalizer::normalize($value, $rules);
    }

    private function normalizeNamedFilterValue(string $name, mixed $value): mixed
    {
        $rules = $this->requestQueryValueRules()['namedFilters'][$name] ?? [];

        return $rules === [] ? $value : RequestQueryValueNormalizer::normalize($value, $rules);
    }

    /**
     * @param  list<mixed>  $parameters
     * @return list<mixed>
     */
    private function normalizeScopeParameters(string $scope, array $parameters): array
    {
        $rules = $this->requestQueryValueRules()['scopes'][$scope] ?? [];
        if ($rules === []) {
            return $parameters;
        }

        return array_map(
            static fn (mixed $parameter): mixed => RequestQueryValueNormalizer::normalize($parameter, $rules),
            $parameters,
        );
    }

    private function normalizeRelationFilterValue(string $relation, string $column, mixed $value): mixed
    {
        $relationRules = $this->requestQueryValueRules()['relations'];
        $rules = $relationRules[$relation][$column]
            ?? $relationRules[$this->resolveRelationAlias($relation)][$column]
            ?? [];

        return $rules === [] ? $value : RequestQueryValueNormalizer::normalize($value, $rules);
    }
}
