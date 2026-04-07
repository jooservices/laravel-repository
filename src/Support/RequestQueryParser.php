<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

use Illuminate\Http\Request;

/**
 * @phpstan-type WhereClause array{column: string, operator: string, value: mixed}
 * @phpstan-type WhereInClause array{column: string, values: list<mixed>}
 * @phpstan-type WhereBetweenClause array{column: string, range: list<mixed>}
 * @phpstan-type ScopeClause array{name: string, parameters: list<mixed>}
 * @phpstan-type HasClause array{relation: string, operator: string, count: int}
 * @phpstan-type NamedFilters array<string, mixed>
 * @phpstan-type WhereHasClause array{
 *     relation: string,
 *     where: list<WhereClause>,
 *     orWhere: list<WhereClause>,
 *     whereIn: list<WhereInClause>,
 *     whereBetween: list<WhereBetweenClause>,
 *     whereNull: list<string>,
 *     whereNotNull: list<string>
 * }
 * @phpstan-type OrderClause array{column: string, direction: string}
 * @phpstan-type QueryClauses array{
 *     where: list<WhereClause>,
 *     orWhere: list<WhereClause>,
 *     whereIn: list<WhereInClause>,
 *     whereBetween: list<WhereBetweenClause>,
 *     whereNull: list<string>,
 *     whereNotNull: list<string>,
 *     fields: list<string>,
 *     filters: NamedFilters,
 *     scope: list<ScopeClause>,
 *     has: list<HasClause>,
 *     whereHas: list<WhereHasClause>,
 *     orWhereHas: list<WhereHasClause>,
 *     whereDoesntHave: list<WhereHasClause>,
 *     orWhereDoesntHave: list<WhereHasClause>,
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

        return [
            'where' => self::parseWhere(self::arrayValue($data, 'where')),
            'orWhere' => self::parseWhere(self::arrayValue($data, 'orWhere')),
            'whereIn' => self::parseWhereIn(self::arrayValue($data, 'whereIn')),
            'whereBetween' => self::parseWhereBetween(self::arrayValue($data, 'whereBetween')),
            'whereNull' => self::parseWhereNull(self::arrayValue($data, 'whereNull')),
            'whereNotNull' => self::parseWhereNotNull(self::arrayValue($data, 'whereNotNull')),
            'fields' => self::parseFields(self::listValue($data, 'fields')),
            'filters' => self::parseFilters(self::arrayValue($data, 'filters')),
            'scope' => self::parseScope(self::listValue($data, 'scope')),
            'has' => RequestQueryHasParser::parse(self::arrayValue($data, 'has')),
            'whereHas' => self::parseRelationClauses(self::arrayValue($data, 'whereHas')),
            'orWhereHas' => self::parseRelationClauses(self::arrayValue($data, 'orWhereHas')),
            'whereDoesntHave' => self::parseRelationClauses(self::arrayValue($data, 'whereDoesntHave')),
            'orWhereDoesntHave' => self::parseRelationClauses(self::arrayValue($data, 'orWhereDoesntHave')),
            'with' => self::parseWith(self::listValue($data, 'with')),
            'order' => self::parseOrder(self::arrayValue($data, 'order')),
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
     * @param  array<string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private static function arrayValue(array $data, string $key): array
    {
        return is_array($data[$key] ?? null) ? $data[$key] : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<mixed>
     */
    private static function listValue(array $data, string $key): array
    {
        $value = $data[$key] ?? [];

        return is_array($value) ? array_values($value) : [$value];
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
            'fields' => [],
            'filters' => [],
            'scope' => [],
            'has' => [],
            'whereHas' => [],
            'orWhereHas' => [],
            'whereDoesntHave' => [],
            'orWhereDoesntHave' => [],
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
            $parsed = self::parseWhereItem($index, $item);
            if ($parsed !== null) {
                $result[] = $parsed;
            }
        }

        return $result;
    }

    /**
     * @return WhereClause|null
     */
    private static function parseWhereItem(int|string $index, mixed $item): ?array
    {
        $result = null;

        if (! is_array($item)) {
            return $result;
        }

        if (isset($item['column'], $item['value'])) {
            $operator = $item['operator'] ?? '=';

            $result = [
                'column' => (string) $item['column'],
                'operator' => is_string($operator) ? $operator : '=',
                'value' => $item['value'],
            ];
        } elseif (is_int($index) && count($item) >= 2) {
            $column = $item[0] ?? null;
            if (! is_string($column)) {
                return null;
            }

            $operator = is_string($item[1] ?? null) ? $item[1] : '=';
            $result = count($item) === 3
                ? [
                    'column' => $column,
                    'operator' => $operator,
                    'value' => $item[2] ?? null,
                ]
                : [
                    'column' => $column,
                    'operator' => '=',
                    'value' => $item[1] ?? null,
                ];
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
        return self::parseWhereNull($items);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<string>
     */
    private static function parseFields(array $items): array
    {
        $result = [];

        foreach ($items as $field) {
            if (! is_string($field)) {
                continue;
            }

            foreach (explode(',', $field) as $segment) {
                $segment = trim($segment);
                if ($segment !== '') {
                    $result[] = $segment;
                }
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return NamedFilters
     */
    private static function parseFilters(array $items): array
    {
        $result = [];

        foreach ($items as $name => $value) {
            if (! is_string($name)) {
                continue;
            }

            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $result[$name] = $value;
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
     * @return list<ScopeClause>
     */
    private static function parseScope(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $name = trim($item);
                if ($name !== '') {
                    $result[] = ['name' => $name, 'parameters' => []];
                }

                continue;
            }

            if (! is_array($item) || $item === []) {
                continue;
            }

            $name = $item['name'] ?? $item[0] ?? null;
            if (! is_string($name)) {
                continue;
            }

            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $parameters = $item['parameters'] ?? array_slice(array_values($item), 1);
            $result[] = [
                'name' => $name,
                'parameters' => is_array($parameters) ? array_values($parameters) : [$parameters],
            ];
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<WhereHasClause>
     */
    private static function parseRelationClauses(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $parsed = self::parseWhereHasItem($item);
            if ($parsed !== null) {
                $result[] = $parsed;
            }
        }

        return $result;
    }

    /**
     * @return WhereHasClause|null
     */
    private static function parseWhereHasItem(mixed $item): ?array
    {
        $result = null;

        if (! is_array($item)) {
            return $result;
        }

        $relation = $item['relation'] ?? $item[0] ?? null;
        if (! is_string($relation)) {
            return $result;
        }

        $relation = trim($relation);
        if ($relation !== '') {
            $result = [
                'relation' => $relation,
                'where' => self::parseWhere(self::arrayClause($item, 'where')),
                'orWhere' => self::parseWhere(self::arrayClause($item, 'orWhere')),
                'whereIn' => self::parseWhereIn(self::arrayClause($item, 'whereIn')),
                'whereBetween' => self::parseWhereBetween(self::arrayClause($item, 'whereBetween')),
                'whereNull' => self::parseWhereNull(self::arrayClause($item, 'whereNull')),
                'whereNotNull' => self::parseWhereNotNull(self::arrayClause($item, 'whereNotNull')),
            ];
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $item
     * @return array<int|string, mixed>
     */
    private static function arrayClause(array $item, string $key): array
    {
        return is_array($item[$key] ?? null) ? $item[$key] : [];
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<OrderClause>
     */
    private static function parseOrder(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (! is_array($item) || empty($item)) {
                continue;
            }

            $column = $item['column'] ?? $item[0] ?? null;
            if (! is_string($column)) {
                continue;
            }

            $column = trim($column);
            if ($column === '') {
                continue;
            }

            $direction = $item['direction'] ?? $item[1] ?? 'asc';
            $direction = is_string($direction) ? strtolower(trim($direction)) : 'asc';

            $result[] = [
                'column' => $column,
                'direction' => in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc',
            ];
        }

        return $result;
    }
}
