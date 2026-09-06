<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;

trait HasCursorPagination
{
    /**
     * @param  array<int, string>|string  $columns
     * @return CursorPaginator<int, Model>
     *
     * @throws InvalidArgumentException
     */
    public function cursorPaginate(
        int $perPage = 15,
        array | string $columns = ['*'],
        string $cursorName = 'cursor',
        ?string $cursor = null,
    ): CursorPaginator {
        $query = $this->getQuery();
        $this->ensureCursorPaginationOrder($query);

        $columnList = is_string($columns) ? [$columns] : array_values($columns);
        $columnList = $this->resolveCursorSelectColumns($query, $columnList);

        try {
            return $query->cursorPaginate($perPage, $columnList, $cursorName, $cursor);
        } finally {
            $this->query = null;
        }
    }

    /**
     * Always append a unique primary-key order when the PK column is not already
     * present in the order list, so duplicate sort values still paginate fully.
     *
     * @param  Builder<*>  $query
     *
     * @throws InvalidArgumentException
     */
    private function ensureCursorPaginationOrder(Builder $query): void
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
     * Merge method-arg and builder projections, retain order/PK columns, and
     * preserve Expression selections (e.g. withCount) plus their select bindings.
     *
     * @param  Builder<*>  $query
     * @param  list<string>  $columns
     * @return list<string>
     */
    private function resolveCursorSelectColumns(Builder $query, array $columns): array
    {
        $base = $query->getQuery();
        $existing = $base->columns;
        $hasBuilderProjection = $this->hasSparseProjection($existing);
        $explicitSparse = $columns !== [] && ! in_array('*', $columns, true);

        if (! $explicitSparse && ! $hasBuilderProjection) {
            return $columns === [] ? ['*'] : $columns;
        }

        $merged = $this->buildCursorProjection($base, $existing, $columns, $hasBuilderProjection, $explicitSparse);
        $base->columns = $merged;

        // Builder columns are authoritative; onceWithColumns keeps them as-is.
        return ['*'];
    }

    /**
     * @param  array<int|string, Expression|string>|null  $existing
     * @param  list<string>  $columns
     * @return list<Expression|string>
     */
    private function buildCursorProjection(
        QueryBuilder $base,
        ?array $existing,
        array $columns,
        bool $hasBuilderProjection,
        bool $explicitSparse,
    ): array {
        $selectBindings = $base->bindings['select'] ?? [];
        /** @var list<Expression|string> $merged */
        $merged = [];

        if ($hasBuilderProjection && is_array($existing)) {
            foreach ($existing as $column) {
                $this->appendProjectionPart($merged, $column);
            }
        }

        if ($explicitSparse) {
            foreach ($columns as $column) {
                $this->appendProjectionPart($merged, $column);
            }
        }

        $this->appendCursorOrderColumns($merged, $base);
        $this->appendProjectionPart(
            $merged,
            $this->getModel()->qualifyColumn($this->getModel()->getKeyName()),
        );

        $base->bindings['select'] = $selectBindings;

        return $merged;
    }

    /**
     * @param  list<Expression|string>  $merged
     */
    private function appendCursorOrderColumns(array &$merged, QueryBuilder $base): void
    {
        foreach ($base->orders ?? [] as $order) {
            if (! is_array($order)) {
                continue;
            }

            $column = $order['column'] ?? null;
            if (! is_string($column) || $column === '') {
                continue;
            }

            $this->appendProjectionPart($merged, $column);
        }
    }

    /**
     * @param  array<int|string, mixed>|null  $columns
     */
    private function hasSparseProjection(?array $columns): bool
    {
        return $columns !== null
            && $columns !== []
            && ! in_array('*', $columns, true);
    }

    /**
     * @param  list<Expression|string>  $columns
     */
    private function appendProjectionPart(array &$columns, mixed $column): void
    {
        if ($column instanceof Expression) {
            foreach ($columns as $existing) {
                if ($existing === $column) {
                    return;
                }
            }

            $columns[] = $column;

            return;
        }

        if (! is_string($column) || $column === '') {
            return;
        }

        $this->appendStringProjectionPart($columns, $column);
    }

    /**
     * @param  list<Expression|string>  $columns
     */
    private function appendStringProjectionPart(array &$columns, string $column): void
    {
        $model = $this->getModel();
        $keyName = $model->getKeyName();
        $qualifiedKey = $model->qualifyColumn($keyName);

        if ($column === $keyName) {
            foreach ($columns as $existing) {
                if ($existing === $qualifiedKey) {
                    return;
                }
            }

            $columns[] = $qualifiedKey;

            return;
        }

        if ($column === $qualifiedKey) {
            $columns = array_values(array_filter(
                $columns,
                static fn(Expression | string $existing): bool => $existing !== $keyName,
            ));
        }

        foreach ($columns as $existing) {
            if ($existing === $column) {
                return;
            }
        }

        $columns[] = $column;
    }
}
