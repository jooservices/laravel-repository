<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

/**
 * @phpstan-import-type NamedFilters from RequestQueryParser
 * @phpstan-import-type OrderClause from RequestQueryParser
 */
class RequestQueryProjectionParser
{
    /**
     * @param  array<int, mixed>  $items
     * @return list<string>
     */
    public static function parseFields(array $items): array
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
    public static function parseFilters(array $items): array
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
     * @return list<string>
     */
    public static function parseWith(array $items): array
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
    public static function parseOrder(array $items): array
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
