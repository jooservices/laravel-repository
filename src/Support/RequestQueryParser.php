<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

use Illuminate\Http\Request;

class RequestQueryParser
{
    /**
     * Parse array payload (filter/query data) into structured clauses.
     *
     * @param  array<string, mixed>  $data  Filter or query array
     * @return array{where: array, orWhere: array, whereIn: array, whereBetween: array,
     *               whereNull: array, whereNotNull: array, with: array, order: array}
     */
    public static function parse(array $data): array
    {
        if (isset($data['filter']) && is_array($data['filter'])) {
            $data = $data['filter'];
        } elseif (isset($data['query']) && is_array($data['query'])) {
            $data = $data['query'];
        }

        return [
            'where' => self::parseWhere($data['where'] ?? []),
            'orWhere' => self::parseWhere($data['orWhere'] ?? []),
            'whereIn' => self::parseWhereIn($data['whereIn'] ?? []),
            'whereBetween' => self::parseWhereBetween($data['whereBetween'] ?? []),
            'whereNull' => self::parseWhereNull($data['whereNull'] ?? []),
            'whereNotNull' => self::parseWhereNotNull($data['whereNotNull'] ?? []),
            'with' => self::parseWith(
                is_array($data['with'] ?? []) ? ($data['with'] ?? []) : [$data['with'] ?? '']
            ),
            'order' => self::parseOrder($data['order'] ?? []),
        ];
    }

    /**
     * Parse from Request (query or body). Uses input key 'filter' or 'query'.
     *
     * @return array{where: array, orWhere: array, whereIn: array, whereBetween: array,
     *               whereNull: array, whereNotNull: array, with: array, order: array}
     */
    public static function fromRequest(Request $request): array
    {
        $data = $request->input('filter') ?? $request->input('query') ?? [];

        if (! is_array($data)) {
            return self::emptyClauses();
        }

        return self::parse($data);
    }

    /**
     * @return array{where: array, orWhere: array, whereIn: array, whereBetween: array,
     *               whereNull: array, whereNotNull: array, with: array, order: array}
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
     * @return array<int, array{column: string, operator?: string, value: mixed}>
     */
    private static function parseWhere(array $items): array
    {
        $result = [];
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            if (isset($item['column'], $item['value'])) {
                $result[] = [
                    'column' => (string) $item['column'],
                    'operator' => $item['operator'] ?? '=',
                    'value' => $item['value'],
                ];
            } elseif (is_int($index) && count($item) >= 2) {
                $column = $item[0];
                if (! is_string($column)) {
                    continue;
                }
                if (count($item) === 3) {
                    $operator = $item[1] ?? '=';
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
     * @return array<int, array{column: string, values: array}>
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
                'values' => is_array($values) ? $values : [$values],
            ];
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<int, array{column: string, range: array}>
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
     * @return array<int, array{column: string, direction: string}>
     */
    private static function parseOrder(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['column'])) {
                $result[] = [
                    'column' => (string) $item['column'],
                    'direction' => $item['direction'] ?? 'asc',
                ];
            } elseif (is_array($item) && count($item) >= 1) {
                $result[] = [
                    'column' => (string) $item[0],
                    'direction' => $item[1] ?? 'asc',
                ];
            }
        }

        return $result;
    }
}
