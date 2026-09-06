<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use Closure;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Attributes\Scope as ScopeAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JOOservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use JOOservices\LaravelRepository\Contracts\ProvidesRequestFiltersInterface;
use JOOservices\LaravelRepository\Contracts\ProvidesRequestQueryMetadataInterface;
use JOOservices\LaravelRepository\Contracts\RequestFilterInterface;
use JOOservices\LaravelRepository\Exceptions\InvalidRequestQueryException;
use JOOservices\LaravelRepository\Exceptions\RepositoryException;
use JOOservices\LaravelRepository\Support\QueryOperator;
use JOOservices\LaravelRepository\Support\RequestQueryInput;
use JOOservices\LaravelRepository\Support\RequestQueryParser;
use JOOservices\LaravelRepository\Support\RequestQueryValueNormalizer;
use JOOservices\LaravelRepository\Support\SkippedRequestValue;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;

/**
 * @phpstan-import-type QueryClauses from RequestQueryParser
 */
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

    /**
     * @throws InvalidRequestQueryException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws RepositoryException
     */
    public function fromRequest(Request $request): static
    {
        $failed = true;

        try {
            $data = $this->requestQueryData($request);
            $this->assertSupportedRequestQuery($data);

            $clauses = RequestQueryParser::parse($data);
            $this->assertSupportedRequestOperators($clauses);

            $query = $this->getQuery();
            $filterValueResolver = function (string $column, mixed $value): mixed {
                return $this->normalizeFilterValue($column, $value);
            };

            $this->applyFieldClauses($query, $clauses['fields'], $clauses['with']);
            $this->applyGroupedRequestFilterClauses($query, $clauses, $filterValueResolver);
            $this->applyScopeClauses($query, $clauses['scope']);
            $this->applyIncludeClauses($query, $clauses['with']);
            $this->applyOrderClauses($query, $clauses['order']);

            $failed = false;

            return $this;
        } finally {
            // Any request-query failure must drop fluent state so later calls start clean,
            // including validation failures that throw before a new builder is created.
            if ($failed) {
                $this->query = null;
            }
        }
    }

    /**
     * Apply request query clauses and paginate with package request per-page guards.
     *
     * @return LengthAwarePaginator<int, Model>
     *
     * @throws InvalidRequestQueryException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws RepositoryException
     */
    public function paginateFromRequest(Request $request, string $perPageKey = 'per_page'): LengthAwarePaginator
    {
        try {
            $this->fromRequest($request);

            return $this->getQuery()->paginate($this->requestPerPage($request, $perPageKey));
        } finally {
            $this->query = null;
        }
    }

    /**
     * Apply request query clauses and cursor-paginate with package request per-page guards.
     *
     * @return CursorPaginator<int, Model>
     *
     * @throws InvalidRequestQueryException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws RepositoryException
     */
    public function cursorPaginateFromRequest(
        Request $request,
        string $perPageKey = 'per_page',
        string $cursorName = 'cursor',
    ): CursorPaginator {
        $this->fromRequest($request);
        $query = $this->getQuery();
        $this->ensureRequestCursorPaginationOrder($query);
        $this->ensureRequestCursorOrderColumnsSelected($query);

        try {
            $cursor = $request->input($cursorName);

            return $query->cursorPaginate(
                $this->requestPerPage($request, $perPageKey),
                ['*'],
                $cursorName,
                is_string($cursor) ? $cursor : null,
            );
        } finally {
            $this->query = null;
        }
    }

    /**
     * Named filters may add joins / selects; those must run on the outer query.
     * Only pure boolean / relation predicates are nested for criteria isolation.
     *
     * @param  QueryClauses  $clauses
     */
    private function hasGroupedRequestFilterClauses(array $clauses): bool
    {
        return $clauses['where'] !== []
            || $clauses['orWhere'] !== []
            || $clauses['whereIn'] !== []
            || $clauses['whereBetween'] !== []
            || $clauses['whereNull'] !== []
            || $clauses['whereNotNull'] !== []
            || $clauses['has'] !== []
            || $clauses['whereHas'] !== []
            || $clauses['orWhereHas'] !== []
            || $clauses['whereDoesntHave'] !== []
            || $clauses['orWhereDoesntHave'] !== [];
    }

    /**
     * Apply named filters on the outer builder, then wrap boolean / relation
     * filters so orWhere / orWhereHas cannot escape repository criteria.
     *
     * @param  Builder<*>  $query
     * @param  QueryClauses  $clauses
     * @param  callable(string, mixed): mixed  $filterValueResolver
     *
     * @throws InvalidRequestQueryException
     * @throws InvalidArgumentException
     */
    private function applyGroupedRequestFilterClauses(
        Builder $query,
        array $clauses,
        callable $filterValueResolver,
    ): void {
        $this->applyNamedFilterClauses($query, $clauses['filters']);

        if (! $this->hasGroupedRequestFilterClauses($clauses)) {
            return;
        }

        $query->where(function (Builder $group) use ($clauses, $filterValueResolver): void {
            $this->applyWhereClauses($group, $clauses['where'], null, $filterValueResolver);
            $this->applyOrWhereClauses($group, $clauses['orWhere'], null, $filterValueResolver);
            $this->applyWhereInClauses($group, $clauses['whereIn'], null, $filterValueResolver);
            $this->applyWhereBetweenClauses($group, $clauses['whereBetween'], null, $filterValueResolver);
            $this->applyWhereNullClauses($group, $clauses['whereNull']);
            $this->applyWhereNotNullClauses($group, $clauses['whereNotNull']);
            $this->applyHasClauses($group, $clauses['has']);
            $this->applyWhereHasClauses($group, $clauses['whereHas']);
            $this->applyOrWhereHasClauses($group, $clauses['orWhereHas']);
            $this->applyWhereDoesntHaveClauses($group, $clauses['whereDoesntHave']);
            $this->applyOrWhereDoesntHaveClauses($group, $clauses['orWhereDoesntHave']);
        });
    }

    /**
     * Always append a unique primary-key order when the PK column is not already
     * present, so duplicate sort values still paginate across all pages.
     *
     * @param  Builder<*>  $query
     *
     * @throws InvalidArgumentException
     */
    private function ensureRequestCursorPaginationOrder(Builder $query): void
    {
        $model = $this->getModel();
        $keyName = $model->getKeyName();
        $qualifiedKey = $model->qualifyColumn($keyName);
        $orders = $query->getQuery()->orders ?? [];

        foreach ($orders as $order) {
            if (! is_array($order)) {
                continue;
            }

            $column = $order['column'] ?? null;
            if (! is_string($column)) {
                continue;
            }

            if ($column === $qualifiedKey || $column === $keyName) {
                return;
            }
        }

        $query->getQuery()->orderBy($qualifiedKey, 'asc');
    }

    /**
     * When a sparse field projection is active, retain order/cursor columns and
     * the qualified primary key without dropping Expression selections
     * (e.g. withCount subselects) or their select bindings.
     *
     * @param  Builder<*>  $query
     */
    private function ensureRequestCursorOrderColumnsSelected(Builder $query): void
    {
        $base = $query->getQuery();
        $columns = $base->columns;
        if ($columns === null || $columns === [] || in_array('*', $columns, true)) {
            return;
        }

        $selectBindings = $base->bindings['select'] ?? [];
        $selected = array_values($columns);

        foreach ($base->orders ?? [] as $order) {
            if (! is_array($order)) {
                continue;
            }

            $column = $order['column'] ?? null;
            if (! is_string($column) || $column === '') {
                continue;
            }

            if (! in_array($column, $selected, true)) {
                $selected[] = $column;
            }
        }

        $model = $this->getModel();
        $keyName = $model->getKeyName();
        $qualifiedKey = $model->qualifyColumn($keyName);
        $selected = array_values(array_filter(
            $selected,
            static fn(mixed $column): bool => $column !== $keyName,
        ));

        if (! in_array($qualifiedKey, $selected, true)) {
            $selected[] = $qualifiedKey;
        }

        $base->columns = $selected;
        $base->bindings['select'] = $selectBindings;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidRequestQueryException
     */
    private function requestQueryData(Request $request): array
    {
        $data = RequestQueryInput::resolve($request);

        if (! is_array($data)) {
            if ($this->requestQueryStrictMode()) {
                throw InvalidRequestQueryException::invalidClause();
            }

            return [];
        }

        $normalized = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidRequestQueryException
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
     * @param  QueryClauses  $clauses
     *
     * @throws InvalidRequestQueryException
     */
    private function assertSupportedRequestOperators(array $clauses): void
    {
        if (! $this->requestQueryStrictMode()) {
            return;
        }

        foreach (['where', 'orWhere'] as $clause) {
            foreach ($clauses[$clause] as $where) {
                QueryOperator::assertSupported($where['operator']);
            }
        }

        foreach (['whereHas', 'orWhereHas', 'whereDoesntHave', 'orWhereDoesntHave'] as $relationClause) {
            foreach ($clauses[$relationClause] as $clause) {
                foreach (['where', 'orWhere'] as $whereClause) {
                    foreach ($clause[$whereClause] as $where) {
                        QueryOperator::assertSupported($where['operator']);
                    }
                }
            }
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $clauses
     * @param  list<string>  $includes
     *
     * @throws InvalidRequestQueryException
     */
    private function applyFieldClauses(Builder $query, array $clauses, array $includes = []): void
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

        $selected = $this->preserveOwnerKeysForIncludes($model, $selected, $includes);

        $query->getQuery()->select(array_values(array_unique($selected)));
    }

    /**
     * Keep root foreign (and morph type) keys required by BelongsTo/MorphTo includes.
     *
     * Sparse field projections that omit these keys cause Eloquent to leave
     * eager-loaded relations null even when the related rows were loaded.
     *
     * Auto-preserved keys are not subject to the fields allowlist: they are
     * internal safety columns for requested root eager loads, not user fields.
     *
     * @param  list<string>  $selected
     * @param  list<string>  $includes
     * @return list<string>
     *
     * @throws InvalidRequestQueryException
     */
    private function preserveOwnerKeysForIncludes(Model $model, array $selected, array $includes): array
    {
        if ($includes === []) {
            return $selected;
        }

        foreach ($this->ownerKeysForIncludes($model, $includes) as $column) {
            if (! in_array($column, $selected, true)) {
                $selected[] = $column;
            }
        }

        return $selected;
    }

    /**
     * @param  list<string>  $includes
     * @return list<string>
     *
     * @throws InvalidRequestQueryException
     */
    private function ownerKeysForIncludes(Model $model, array $includes): array
    {
        $ownerKeys = [];

        foreach ($includes as $requestedInclude) {
            foreach ($this->ownerKeysForInclude($model, $requestedInclude) as $column) {
                $ownerKeys[] = $column;
            }
        }

        return array_values(array_unique($ownerKeys));
    }

    /**
     * @return list<string>
     *
     * @throws InvalidRequestQueryException
     */
    private function ownerKeysForInclude(Model $model, string $requestedInclude): array
    {
        $include = $this->resolveIncludeRequest($requestedInclude);
        if ($include === null || $include['type'] !== 'relation') {
            return [];
        }

        $rootRelation = Str::before($include['relation'], '.');
        if (
            $rootRelation === ''
            || ! method_exists($model, $rootRelation)
            || ! $this->relationExists($rootRelation)
        ) {
            return [];
        }

        $relationCaller = [$model, $rootRelation];
        if (! is_callable($relationCaller)) {
            return [];
        }

        $relation = $relationCaller();
        if ($relation instanceof MorphTo) {
            return [
                $model->qualifyColumn($relation->getForeignKeyName()),
                $model->qualifyColumn($relation->getMorphType()),
            ];
        }

        if ($relation instanceof BelongsTo) {
            return [$model->qualifyColumn($relation->getForeignKeyName())];
        }

        return [];
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<string, mixed>  $clauses
     *
     * @throws InvalidRequestQueryException
     * @throws InvalidArgumentException
     */
    private function applyNamedFilterClauses(Builder $query, array $clauses): void
    {
        foreach ($clauses as $name => $value) {
            $filter = $this->resolveRequestFilter($name);
            if ($filter === null) {
                continue;
            }

            $value = $this->normalizeNamedFilterValue($name, $value);
            if ($value instanceof SkippedRequestValue) {
                continue;
            }

            $this->applyNamedFilterIsolated($query, $filter, $value);
        }
    }

    /**
     * Run a named filter on an isolated builder so orWhere cannot escape
     * repository criteria, then promote non-boolean query state (joins, select,
     * order, limit, group, …) to the outer query.
     *
     * @param  Builder<*>  $query
     * @param  RequestFilterInterface|(callable(Builder<*>, mixed): void)  $filter
     *
     * @throws InvalidArgumentException
     */
    private function applyNamedFilterIsolated(Builder $query, mixed $filter, mixed $value): void
    {
        $nested = $query->getModel()->newQueryWithoutRelationships();

        if ($filter instanceof RequestFilterInterface) {
            $filter->apply($nested, $value);
        } else {
            $filter($nested, $value);
        }

        $this->promoteNamedFilterQueryState($query, $nested);
        $query->getQuery()->addNestedWhereQuery($nested->getQuery(), 'and');
    }

    /**
     * @param  Builder<*>  $outer
     * @param  Builder<*>  $nested
     *
     * @throws InvalidArgumentException
     */
    private function promoteNamedFilterQueryState(Builder $outer, Builder $nested): void
    {
        $nestedBase = $nested->getQuery();
        $outerBase = $outer->getQuery();

        $this->promoteNamedFilterJoins($outerBase, $nestedBase);
        $this->promoteNamedFilterColumns($outerBase, $nestedBase);
        $this->promoteNamedFilterOrders($outerBase, $nestedBase);
        $this->promoteNamedFilterGroups($outerBase, $nestedBase);
        $this->promoteNamedFilterHavings($outerBase, $nestedBase);

        if ($nestedBase->distinct === true || is_array($nestedBase->distinct)) {
            $outerBase->distinct = $nestedBase->distinct;
        }

        if ($nestedBase->limit !== null) {
            $outerBase->limit = $nestedBase->limit;
        }

        if ($nestedBase->offset !== null) {
            $outerBase->offset = $nestedBase->offset;
        }
    }

    /**
     * @param  QueryBuilder  $outerBase
     * @param  QueryBuilder  $nestedBase
     *
     * @throws InvalidArgumentException
     */
    private function promoteNamedFilterJoins(QueryBuilder $outerBase, QueryBuilder $nestedBase): void
    {
        $joins = $nestedBase->joins ?? [];

        if ($joins === []) {
            return;
        }

        $outerBase->joins = array_values([
            ...($outerBase->joins ?? []),
            ...$joins,
        ]);

        foreach ($nestedBase->bindings['join'] ?? [] as $binding) {
            $outerBase->addBinding($binding, 'join');
        }

        $nestedBase->joins = null;
        $nestedBase->bindings['join'] = [];
    }

    /**
     * @param  QueryBuilder  $outerBase
     * @param  QueryBuilder  $nestedBase
     *
     * @throws InvalidArgumentException
     */
    private function promoteNamedFilterColumns(QueryBuilder $outerBase, QueryBuilder $nestedBase): void
    {
        $columns = $nestedBase->columns;

        if ($columns === null || $columns === []) {
            return;
        }

        $outerColumns = $outerBase->columns;
        if ($outerColumns === null || $outerColumns === [] || in_array('*', $outerColumns, true)) {
            $outerBase->columns = array_values($columns);
        } else {
            $outerBase->columns = array_values([...$outerColumns, ...$columns]);
        }

        foreach ($nestedBase->bindings['select'] ?? [] as $binding) {
            $outerBase->addBinding($binding, 'select');
        }

        $nestedBase->columns = null;
        $nestedBase->bindings['select'] = [];
    }

    /**
     * @param  QueryBuilder  $outerBase
     * @param  QueryBuilder  $nestedBase
     *
     * @throws InvalidArgumentException
     */
    private function promoteNamedFilterOrders(QueryBuilder $outerBase, QueryBuilder $nestedBase): void
    {
        $orders = $nestedBase->orders ?? [];

        if ($orders === []) {
            return;
        }

        $outerBase->orders = array_values([
            ...($outerBase->orders ?? []),
            ...$orders,
        ]);

        foreach ($nestedBase->bindings['order'] ?? [] as $binding) {
            $outerBase->addBinding($binding, 'order');
        }

        $nestedBase->orders = null;
        $nestedBase->bindings['order'] = [];
    }

    /**
     * @param  QueryBuilder  $outerBase
     * @param  QueryBuilder  $nestedBase
     */
    private function promoteNamedFilterGroups(QueryBuilder $outerBase, QueryBuilder $nestedBase): void
    {
        $groups = $nestedBase->groups ?? [];

        if ($groups === []) {
            return;
        }

        $outerBase->groups = array_values([
            ...($outerBase->groups ?? []),
            ...$groups,
        ]);
        $nestedBase->groups = null;
    }

    /**
     * @param  QueryBuilder  $outerBase
     * @param  QueryBuilder  $nestedBase
     *
     * @throws InvalidArgumentException
     */
    private function promoteNamedFilterHavings(QueryBuilder $outerBase, QueryBuilder $nestedBase): void
    {
        $havings = $nestedBase->havings ?? [];

        if ($havings === []) {
            return;
        }

        $outerBase->havings = array_values([
            ...($outerBase->havings ?? []),
            ...$havings,
        ]);

        foreach ($nestedBase->bindings['having'] ?? [] as $binding) {
            $outerBase->addBinding($binding, 'having');
        }

        $nestedBase->havings = null;
        $nestedBase->bindings['having'] = [];
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, operator: string, value: mixed}>  $clauses
     * @param  (callable(string): bool)|null  $guard
     * @param  (callable(string, mixed): mixed)|null  $valueResolver
     *
     * @throws InvalidRequestQueryException
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

            $value = $valueResolver !== null
                ? $valueResolver($where['column'], $where['value'])
                : $where['value'];

            if ($value instanceof SkippedRequestValue) {
                continue;
            }

            QueryOperator::apply($query, 'where', $column, $where['operator'], $value);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, operator: string, value: mixed}>  $clauses
     * @param  (callable(string): bool)|null  $guard
     * @param  (callable(string, mixed): mixed)|null  $valueResolver
     *
     * @throws InvalidRequestQueryException
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

            $value = $valueResolver !== null
                ? $valueResolver($where['column'], $where['value'])
                : $where['value'];

            if ($value instanceof SkippedRequestValue) {
                continue;
            }

            QueryOperator::apply($query, 'orWhere', $column, $where['operator'], $value);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, values: list<mixed>}>  $clauses
     * @param  (callable(string): bool)|null  $guard
     * @param  (callable(string, mixed): mixed)|null  $valueResolver
     *
     * @throws InvalidRequestQueryException
     * @throws InvalidArgumentException
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

            $values = $valueResolver !== null
                ? $valueResolver($whereIn['column'], $whereIn['values'])
                : $whereIn['values'];

            if ($values instanceof SkippedRequestValue) {
                continue;
            }

            $query->getQuery()->whereIn($column, is_array($values) ? $values : [$values]);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, range: list<mixed>}>  $clauses
     * @param  (callable(string): bool)|null  $guard
     * @param  (callable(string, mixed): mixed)|null  $valueResolver
     *
     * @throws InvalidRequestQueryException
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

            $range = $valueResolver !== null
                ? $valueResolver($whereBetween['column'], $whereBetween['range'])
                : $whereBetween['range'];

            if ($range instanceof SkippedRequestValue) {
                continue;
            }

            $range = is_array($range) ? array_values($range) : [];

            if (count($range) >= 2) {
                $query->getQuery()->whereBetween($column, $range);
            }
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $clauses
     * @param  (callable(string): bool)|null  $guard
     *
     * @throws InvalidRequestQueryException
     */
    private function applyWhereNullClauses(Builder $query, array $clauses, ?callable $guard = null): void
    {
        foreach ($clauses as $column) {
            $resolvedColumn = $this->resolveRequestedFilterColumn($column, $guard);
            if ($resolvedColumn === null) {
                continue;
            }

            $query->getQuery()->whereNull($resolvedColumn);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $clauses
     * @param  (callable(string): bool)|null  $guard
     *
     * @throws InvalidRequestQueryException
     */
    private function applyWhereNotNullClauses(Builder $query, array $clauses, ?callable $guard = null): void
    {
        foreach ($clauses as $column) {
            $resolvedColumn = $this->resolveRequestedFilterColumn($column, $guard);
            if ($resolvedColumn === null) {
                continue;
            }

            $query->getQuery()->whereNotNull($resolvedColumn);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $clauses
     *
     * @throws InvalidRequestQueryException
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

            if ($include['type'] === 'relation') {
                $query->with($relation);
            } elseif ($include['type'] === 'count') {
                $query->withCount([$relation . ' as ' . $include['attribute']]);
            } elseif ($include['type'] === 'exists') {
                $query->withExists([$relation . ' as ' . $include['attribute']]);
            } elseif ($include['type'] === 'sum') {
                $query->withSum($relation . ' as ' . $include['attribute'], $include['column']);
            } elseif ($include['type'] === 'avg') {
                $query->withAvg($relation . ' as ' . $include['attribute'], $include['column']);
            } elseif ($include['type'] === 'min') {
                $query->withMin($relation . ' as ' . $include['attribute'], $include['column']);
            } else {
                $query->withMax($relation . ' as ' . $include['attribute'], $include['column']);
            }
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{name: string, parameters: list<mixed>}>  $clauses
     *
     * @throws InvalidRequestQueryException
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

            /** @see Builder::__call */
            $scopeCaller = [$query, Str::camel($definition['scope'])];
            if (! is_callable($scopeCaller)) {
                continue;
            }

            call_user_func_array($scopeCaller, $definition['parameters']);
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{relation: string, operator: string, count: int}>  $clauses
     *
     * @throws InvalidRequestQueryException
     * @throws RuntimeException
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
     *
     * @throws InvalidRequestQueryException
     * @throws RepositoryException
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
     *
     * @throws InvalidRequestQueryException
     * @throws RepositoryException
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
     *
     * @throws InvalidRequestQueryException
     * @throws RepositoryException
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
     *
     * @throws InvalidRequestQueryException
     * @throws RepositoryException
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
     *
     * @throws InvalidRequestQueryException
     * @throws RepositoryException
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
                $guard = fn(string $column): bool => $this->shouldApplyRelationColumn($relation, $column);
                $valueResolver = fn(string $column, mixed $value): mixed => $this->normalizeRelationFilterValue(
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
                default => throw new RepositoryException(sprintf('Unsupported relation clause method [%s].', $method)),
            };
        }
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<array{column: string, direction: string}>  $clauses
     *
     * @throws InvalidRequestQueryException
     * @throws InvalidArgumentException
     */
    private function applyOrderClauses(Builder $query, array $clauses): void
    {
        foreach ($clauses as $order) {
            if (! $this->shouldApplySort($order['column'])) {
                continue;
            }

            $direction = strtolower(trim($order['direction'])) === 'desc' ? 'desc' : 'asc';
            $query->getQuery()->orderBy($order['column'], $direction);
        }
    }

    /**
     * @throws InvalidRequestQueryException
     */
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

    /**
     * @throws InvalidRequestQueryException
     */
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

    /**
     * @throws InvalidRequestQueryException
     */
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

    /**
     * @throws InvalidRequestQueryException
     */
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
     *
     * @throws InvalidRequestQueryException
     */
    private function guardAllowedValue(string $value, ?array $allowed, callable $exceptionFactory): bool
    {
        if ($allowed === null && ! $this->requestQueryStrictMode()) {
            return true;
        }

        if ($allowed !== null && in_array($value, $allowed, true)) {
            return true;
        }

        if ($this->requestQueryStrictMode()) {
            throw $exceptionFactory($value, $allowed ?? []);
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

    /**
     * @throws InvalidRequestQueryException
     */
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

    /**
     * @throws InvalidRequestQueryException
     */
    private function shouldApplyRelationFilter(string $relation): bool
    {
        $allowed = $this->requestQueryVisibleRelationFilters();
        if ($allowed === null && ! $this->requestQueryStrictMode()) {
            return true;
        }

        if ($allowed !== null && in_array($relation, $allowed, true)) {
            return true;
        }

        if ($this->requestQueryStrictMode()) {
            throw InvalidRequestQueryException::disallowedRelation($relation, $allowed ?? []);
        }

        return false;
    }

    /**
     * @throws InvalidRequestQueryException
     */
    private function shouldApplyRelationCount(string $relation): bool
    {
        $allowed = $this->requestQueryVisibleRelationCounts();
        if ($allowed === null && ! $this->requestQueryStrictMode()) {
            return true;
        }

        if ($allowed !== null && in_array($relation, $allowed, true)) {
            return true;
        }

        if ($this->requestQueryStrictMode()) {
            throw InvalidRequestQueryException::disallowedRelationDetail('count', $relation, $allowed ?? []);
        }

        return false;
    }

    /**
     * @throws InvalidRequestQueryException
     */
    private function shouldApplyRelationColumn(string $relation, string $column): bool
    {
        $allowed = $this->requestQueryAllowedRelationFilters();
        if ($allowed === null && ! $this->requestQueryStrictMode()) {
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
     *
     * @throws InvalidRequestQueryException
     */
    private function resolveRequestFilter(string $name): RequestFilterInterface | Closure | null
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
     *
     * @throws InvalidRequestQueryException
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

    private function scopeExists(string $scope): bool
    {
        $model = $this->getModel();

        if (method_exists($model, 'scope' . Str::studly($scope))) {
            return true;
        }

        $attributeMethod = Str::camel($scope);
        if (! method_exists($model, $attributeMethod)) {
            return false;
        }

        try {
            $method = new ReflectionMethod($model, $attributeMethod);
        } catch (ReflectionException) {
            return false;
        }

        if ($method->isPrivate()) {
            return false;
        }

        return $method->getAttributes(ScopeAttribute::class) !== [];
    }

    /**
     * @param  list<mixed>  $parameters
     * @return array{scope: string, parameters: list<mixed>}|null
     *
     * @throws InvalidRequestQueryException
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
     * @return (
     *     array{type: 'relation'|'count'|'exists', relation: string, attribute: string}|
     *     array{type: 'sum'|'avg'|'min'|'max', relation: string, attribute: string, column: string}
     * )|null
     *
     * @throws InvalidRequestQueryException
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

        if (Str::endsWith($relation, 'Count')) {
            $base = Str::beforeLast($relation, 'Count');

            return [
                'type' => 'count',
                'relation' => $this->resolveRelationAlias($base),
                'attribute' => Str::snake($base) . '_count',
            ];
        }

        if (Str::endsWith($relation, 'Exists')) {
            $base = Str::beforeLast($relation, 'Exists');

            return [
                'type' => 'exists',
                'relation' => $this->resolveRelationAlias($base),
                'attribute' => Str::snake($base) . '_exists',
            ];
        }

        return [
            'type' => 'relation',
            'relation' => $this->resolveRelationAlias($relation),
            'attribute' => '',
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
     * @return array<string, array{
     *     relation: string,
     *     column: string,
     *     function: 'sum'|'avg'|'min'|'max',
     *     attribute: string
     * }>
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
            static fn(string $name): bool => ! in_array($name, $aliasedTargets, true),
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
            $derived[] = $relation . 'Count';
            $derived[] = $relation . 'Exists';
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
            static fn(string $name): bool => ! in_array($name, $aliasedTargets, true),
        )));
    }

    private function relationExists(string $relation): bool
    {
        $model = $this->getModel();
        $path = '';

        foreach (explode('.', $relation) as $segment) {
            $path = $path === '' ? $segment : $path . '.' . $segment;

            if (! method_exists($model, $segment)) {
                return false;
            }

            try {
                $method = new ReflectionMethod($model, $segment);
            } catch (ReflectionException) {
                return false;
            }

            if (! $this->isSafeRelationMethod($method, $path)) {
                return false;
            }

            $relationCaller = [$model, $segment];
            if (! is_callable($relationCaller)) {
                return false;
            }

            $relationObject = $relationCaller();
            if (! $relationObject instanceof Relation) {
                return false;
            }

            $model = $relationObject->getRelated();
        }

        return true;
    }

    /**
     * Relation methods must declare a Relation return type, unless the path is
     * explicitly trusted via allowlisted includes, relation-filter keys, or
     * {@see HasAllowedRequestQuery::$trustedRelations}. Untyped methods are
     * never accepted when allowlists are unrestricted (`null`).
     */
    private function isSafeRelationMethod(ReflectionMethod $method, string $relationPath): bool
    {
        if (! $method->isPublic() || $method->getNumberOfRequiredParameters() > 0) {
            return false;
        }

        $returnType = $method->getReturnType();
        if ($returnType === null) {
            return $this->isTrustedUntypedRelation($relationPath);
        }

        return $this->returnTypeContainsRelation($returnType);
    }

    private function isTrustedUntypedRelation(string $relationPath): bool
    {
        foreach ($this->trustedRelationPaths() as $trusted) {
            if ($this->relationPathCovers($trusted, $relationPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function trustedRelationPaths(): array
    {
        $paths = [];

        if (property_exists($this, 'trustedRelations') && is_array($this->trustedRelations)) {
            foreach ($this->trustedRelations as $relation) {
                if (is_string($relation) && $relation !== '') {
                    $paths[] = $relation;
                }
            }
        }

        if (! $this instanceof AllowsRequestQueryInterface) {
            return array_values(array_unique($paths));
        }

        $includes = $this->allowedIncludes();
        if ($includes !== null) {
            foreach ($includes as $include) {
                $paths[] = $this->relationPathFromInclude($include);
            }
        }

        $relationFilters = $this->allowedRelationFilters();
        if ($relationFilters !== null) {
            foreach (array_keys($relationFilters) as $relation) {
                $paths[] = $relation;
            }
        }

        return array_values(array_unique($paths));
    }

    private function relationPathFromInclude(string $include): string
    {
        foreach (['Count', 'Exists', 'Sum', 'Avg', 'Min', 'Max'] as $suffix) {
            if (str_ends_with($include, $suffix) && $include !== $suffix) {
                return substr($include, 0, -strlen($suffix));
            }
        }

        return $include;
    }

    private function relationPathCovers(string $trusted, string $relationPath): bool
    {
        // Exact trust, or an ancestor segment while walking a longer trusted path
        // (e.g. trusted "posts.comments" while validating "posts"). Descendants of
        // a short trust (e.g. "peers.auditEffect" under "peers") are not authorized.
        return $trusted === $relationPath
            || str_starts_with($trusted, $relationPath . '.');
    }

    private function returnTypeContainsRelation(ReflectionType $type): bool
    {
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return false;
            }

            $name = $type->getName();

            return $name === Relation::class || is_a($name, Relation::class, true);
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $inner) {
                if ($this->returnTypeContainsRelation($inner)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function requestQueryStrictMode(): bool
    {
        return $this instanceof AllowsRequestQueryInterface && $this->isRequestQueryStrict();
    }

    /**
     * @throws InvalidRequestQueryException
     */
    private function requestPerPage(Request $request, string $key): int
    {
        $default = max(1, $this->configInt('laravel-repository.default_per_page', 15));
        $max = max(1, $this->configInt('laravel-repository.max_per_page', 100));
        $value = $request->input($key);

        if ($value === null || $value === '') {
            return min($default, $max);
        }

        $validated = filter_var($value, FILTER_VALIDATE_INT);
        if ($validated === false || $validated < 1) {
            if ($this->requestQueryStrictMode()) {
                throw new InvalidRequestQueryException(sprintf(
                    'Request query per-page value [%s] must be an integer greater than or equal to 1.',
                    is_scalar($value) ? (string) $value : get_debug_type($value),
                ));
            }

            return min($default, $max);
        }

        $perPage = $validated;
        if ($perPage > $max) {
            if ($this->requestQueryStrictMode()) {
                throw new InvalidRequestQueryException(sprintf(
                    'Request query per-page value [%d] exceeds the configured maximum of [%d].',
                    $perPage,
                    $max,
                ));
            }

            return $max;
        }

        return $perPage;
    }

    private function configInt(string $key, int $default): int
    {
        $value = config($key, $default);
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
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
            static fn(mixed $parameter): mixed => RequestQueryValueNormalizer::normalize($parameter, $rules),
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
