<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

use Illuminate\Database\Eloquent\Builder;

class QueryOperator
{
    /**
     * @param  Builder<*>  $query
     */
    public static function apply(Builder $query, string $method, string $column, string $operator, mixed $value): void
    {
        $normalized = self::normalize($operator);

        match ($normalized) {
            'exact' => $query->{$method}($column, '=', $value),
            'partial' => $query->{$method}($column, 'like', self::wrapValue($value, '%', '%')),
            'beginswith' => $query->{$method}($column, 'like', self::wrapValue($value, '', '%')),
            'endswith' => $query->{$method}($column, 'like', self::wrapValue($value, '%', '')),
            default => $query->{$method}($column, $operator, $value),
        };
    }

    public static function normalize(string $operator): string
    {
        return strtolower(str_replace([' ', '_'], '', trim($operator)));
    }

    private static function wrapValue(mixed $value, string $prefix, string $suffix): string
    {
        return $prefix.(string) $value.$suffix;
    }
}
