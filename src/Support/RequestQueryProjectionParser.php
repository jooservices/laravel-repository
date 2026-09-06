<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Support;

/**
 * @phpstan-import-type NamedFilters from RequestQueryParser
 * @phpstan-import-type OrderClause from RequestQueryParser
 */
final class RequestQueryProjectionParser
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

        foreach ($items as $key => $item) {
            if (is_string($key)) {
                $parsed = self::parseOrderFromKeyedItem($key, $item);
                if ($parsed !== null) {
                    $result[] = $parsed;
                }

                continue;
            }

            if (is_string($item)) {
                foreach (self::parseOrderShorthand($item) as $order) {
                    $result[] = $order;
                }

                continue;
            }

            $parsed = self::parseOrderFromArrayItem($item);
            if ($parsed !== null) {
                $result[] = $parsed;
            }
        }

        return $result;
    }

    /**
     * @return OrderClause|null
     */
    private static function parseOrderFromKeyedItem(string $key, mixed $item): ?array
    {
        $column = trim($key);
        if ($column === '') {
            return null;
        }

        if (str_starts_with($column, '-')) {
            $column = trim(substr($column, 1));
            if ($column === '') {
                return null;
            }

            return ['column' => $column, 'direction' => 'desc'];
        }

        $direction = is_string($item) ? strtolower(trim($item)) : 'asc';

        return [
            'column' => $column,
            'direction' => in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc',
        ];
    }

    /**
     * @return OrderClause|null
     */
    private static function parseOrderFromArrayItem(mixed $item): ?array
    {
        if (! is_array($item) || $item === []) {
            return null;
        }

        $column = $item['column'] ?? $item[0] ?? null;
        if (! is_string($column)) {
            return null;
        }

        $column = trim($column);
        if ($column === '') {
            return null;
        }

        if (str_starts_with($column, '-')) {
            $column = trim(substr($column, 1));
            if ($column === '') {
                return null;
            }

            return ['column' => $column, 'direction' => 'desc'];
        }

        $direction = $item['direction'] ?? $item[1] ?? 'asc';
        $direction = is_string($direction) ? strtolower(trim($direction)) : 'asc';

        return [
            'column' => $column,
            'direction' => in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc',
        ];
    }

    /**
     * @return list<OrderClause>
     */
    private static function parseOrderShorthand(string $value): array
    {
        $result = [];

        foreach (explode(',', $value) as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (str_starts_with($segment, '-')) {
                $column = trim(substr($segment, 1));
                if ($column !== '') {
                    $result[] = ['column' => $column, 'direction' => 'desc'];
                }

                continue;
            }

            $result[] = ['column' => $segment, 'direction' => 'asc'];
        }

        return $result;
    }
}
