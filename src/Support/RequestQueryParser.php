<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

use Illuminate\Http\Request;

/**
 * @phpstan-type WhereClause array{column: string, operator: string, value: mixed}
 * @phpstan-type WhereInClause array{column: string, values: list<mixed>}
 * @phpstan-type WhereBetweenClause array{column: string, range: list<mixed>}
 * @phpstan-type OrderClause array{column: string, direction: string}
 * @phpstan-type QueryClauses array{
 *     where: list<WhereClause>,
 *     orWhere: list<WhereClause>,
 *     whereIn: list<WhereInClause>,
 *     whereBetween: list<WhereBetweenClause>,
 *     whereNull: list<string>,
 *     whereNotNull: list<string>,
 *     with: list<string>,
 *     order: list<OrderClause>
 * }
 */
class RequestQueryParser
{
    /**
     * Parse array payload (filter/query data) into structured clauses.
     *
     * @param  array<string, mixed>  $data  Filter or query array
     * @return QueryClauses
     */
    public static function parse(array $data): array
    {
        if (isset($data['filter']) && is_array($data['filter'])) {
            $data = $data['filter'];
        } elseif (isset($data['query']) && is_array($data['query'])) {
            $data = $data['query'];
        }

        $where = $data['where'] ?? [];
        $orWhere = $data['orWhere'] ?? [];
        $whereIn = $data['whereIn'] ?? [];
        $whereBetween = $data['whereBetween'] ?? [];
        $whereNull = $data['whereNull'] ?? [];
        $whereNotNull = $data['whereNotNull'] ?? [];
        $with = $data['with'] ?? [];
        $order = $data['order'] ?? [];

        return [
            'where' => self::parseWhere(is_array($where) ? $where : []),
            'orWhere' => self::parseWhere(is_array($orWhere) ? $orWhere : []),
            'whereIn' => self::parseWhereIn(is_array($whereIn) ? $whereIn : []),
            'whereBetween' => self::parseWhereBetween(is_array($whereBetween) ? $whereBetween : []),
            'whereNull' => self::parseWhereNull(is_array($whereNull) ? $whereNull : []),
            'whereNotNull' => self::parseWhereNotNull(is_array($whereNotNull) ? $whereNotNull : []),
            'with' => self::parseWith(
                is_array($with) ? $with : [$with],
            ),
            'order' => self::parseOrder(is_array($order) ? $order : []),
        ];
    }

    /**
     * Parse from Request (query or body). Uses input key 'filter' or 'query'.
     *
     * @return QueryClauses
     */
    public static function fromRequest(Request $request): array
    {
        $data = $request->input('filter') ?? $request->input('query') ?? [];

        if (! is_array($data)) {
            return self::emptyClauses();
        }

        return self::parse(self::normalizeRootData($data));
    }

    /**
     * @param  array<mixed, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeRootData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return QueryClauses
     */
    private static function emptyClauses(): array
    {
        return [
            'where' => [],
            'orWhere' => [],
            'whereIn' => [],
            'whereBetween' => [],
            'whereNull' => [],
            'whereNotNull' => [],
            'with' => [],
            'order' => [],
        ];
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<WhereClause>
     */
    private static function parseWhere(array $items): array
    {
        $result = [];
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            if (isset($item['column'], $item['value'])) {
                $operator = $item['operator'] ?? '=';
                $result[] = [
                    'column' => (string) $item['column'],
                    'operator' => is_string($operator) ? $operator : '=',
                    'value' => $item['value'],
                ];
            } elseif (is_int($index) && count($item) >= 2) {
                $column = $item[0];
                if (! is_string($column)) {
                    continue;
                }
                if (count($item) === 3) {
                    $operator = is_string($item[1] ?? null) ? $item[1] : '=';
                    $value = $item[2] ?? null;
                } else {
                    $value = $item[1] ?? null;
                    $operator = '=';
                }
                $result[] = ['column' => $column, 'operator' => $operator, 'value' => $value];
            }
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<WhereInClause>
     */
    private static function parseWhereIn(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['column'])) {
                continue;
            }
            $values = $item['values'] ?? $item['value'] ?? [];
            $result[] = [
                'column' => (string) $item['column'],
                'values' => is_array($values) ? array_values($values) : [$values],
            ];
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<WhereBetweenClause>
     */
    private static function parseWhereBetween(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['column'])) {
                continue;
            }
            $range = $item['range'] ?? $item['value'] ?? [];
            $result[] = [
                'column' => (string) $item['column'],
                'range' => is_array($range) ? array_values($range) : [],
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, string>|array<string, mixed>  $items
     * @return array<int, string>
     */
    private static function parseWhereNull(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $column = is_array($item) ? ($item['column'] ?? $item[0] ?? null) : $item;
            if (is_string($column)) {
                $result[] = $column;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, string>|array<string, mixed>  $items
     * @return array<int, string>
     */
    private static function parseWhereNotNull(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $column = is_array($item) ? ($item['column'] ?? $item[0] ?? null) : $item;
            if (is_string($column)) {
                $result[] = $column;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, string>
     */
    private static function parseWith(array $items): array
    {
        $result = [];
        foreach ($items as $relation) {
            if (is_string($relation) && $relation !== '') {
                $result[] = $relation;
            }
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<OrderClause>
     */
    private static function parseOrder(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['column'])) {
                $direction = $item['direction'] ?? 'asc';
                $result[] = [
                    'column' => (string) $item['column'],
                    'direction' => is_string($direction) ? $direction : 'asc',
                ];
            } elseif (is_array($item) && count($item) >= 1) {
                $direction = $item[1] ?? 'asc';
                $result[] = [
                    'column' => (string) $item[0],
                    'direction' => is_string($direction) ? $direction : 'asc',
                ];
            }
        }

        return $result;
    }
}
