<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

use Illuminate\Database\Eloquent\Builder;
use Jooservices\LaravelRepository\Exceptions\InvalidRequestQueryException;

class QueryOperator
{
    /**
     * @var array<string, string>
     */
    private const SQL_ALIASES = [
        'eq' => '=',
        'neq' => '!=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'like' => 'like',
        '=' => '=',
        '!=' => '!=',
        '<>' => '!=',
        '>' => '>',
        '>=' => '>=',
        '<' => '<',
        '<=' => '<=',
    ];

    /**
     * @var list<string>
     */
    private const NAMED_OPERATORS = [
        'exact',
        'partial',
        'beginswith',
        'endswith',
    ];

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
            default => $query->{$method}($column, self::SQL_ALIASES[$normalized] ?? $operator, $value),
        };
    }

    public static function normalize(string $operator): string
    {
        return strtolower(str_replace([' ', '_'], '', trim($operator)));
    }

    public static function isSupported(string $operator): bool
    {
        $normalized = self::normalize($operator);

        return in_array($normalized, self::NAMED_OPERATORS, true)
            || array_key_exists($normalized, self::SQL_ALIASES);
    }

    public static function assertSupported(string $operator): void
    {
        if (! self::isSupported($operator)) {
            throw new InvalidRequestQueryException(sprintf(
                'Request query operator [%s] is not supported. Supported operators: %s.',
                $operator,
                implode(', ', self::supportedOperators()),
            ));
        }
    }

    /**
     * @return list<string>
     */
    public static function supportedOperators(): array
    {
        return [
            'exact',
            'partial',
            'beginsWith',
            'endsWith',
            'eq',
            'neq',
            'gt',
            'gte',
            'lt',
            'lte',
            'like',
            '=',
            '!=',
            '<>',
            '>',
            '>=',
            '<',
            '<=',
        ];
    }

    private static function wrapValue(mixed $value, string $prefix, string $suffix): string
    {
        return $prefix.(string) $value.$suffix;
    }
}
