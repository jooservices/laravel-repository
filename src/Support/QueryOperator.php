<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Support;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use JOOservices\LaravelRepository\Exceptions\InvalidRequestQueryException;

final class QueryOperator
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
        'before',
        'after',
        'date',
        'jsoncontains',
    ];

    /**
     * @param  Builder<*>  $query
     *
     * @throws InvalidRequestQueryException
     */
    public static function apply(Builder $query, string $method, string $column, string $operator, mixed $value): void
    {
        $normalized = self::normalize($operator);

        match ($normalized) {
            'exact' => self::callWhere($query, $method, $column, '=', $value),
            'partial' => self::callWhere($query, $method, $column, 'like', self::wrapValue($value, '%', '%')),
            'beginswith' => self::callWhere($query, $method, $column, 'like', self::wrapValue($value, '', '%')),
            'endswith' => self::callWhere($query, $method, $column, 'like', self::wrapValue($value, '%', '')),
            'before' => self::callWhere($query, $method, $column, '<', $value),
            'after' => self::callWhere($query, $method, $column, '>', $value),
            'date' => self::callWhereDate($query, $method, $column, $value),
            'jsoncontains' => self::callJsonContains($query, $method, $column, $value),
            default => self::callWhere($query, $method, $column, self::SQL_ALIASES[$normalized] ?? $operator, $value),
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

    /**
     * @throws InvalidRequestQueryException
     */
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
            'before',
            'after',
            'date',
            'jsonContains',
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

    /**
     * @param  Builder<*>  $query
     *
     * @throws InvalidRequestQueryException
     */
    private static function callWhere(
        Builder $query,
        string $method,
        string $column,
        string $operator,
        mixed $value,
    ): void {
        match ($method) {
            'where' => $query->where($column, $operator, $value),
            'orWhere' => $query->orWhere($column, $operator, $value),
            default => throw new InvalidRequestQueryException(sprintf(
                'Request query method [%s] is not supported. Supported methods: where, orWhere.',
                $method,
            )),
        };
    }

    /**
     * @param  Builder<*>  $query
     *
     * @throws InvalidRequestQueryException
     */
    private static function callWhereDate(Builder $query, string $method, string $column, mixed $value): void
    {
        if (! ($value instanceof DateTimeInterface || is_string($value) || $value === null)) {
            throw new InvalidRequestQueryException(
                'Request query date operator value must be DateTimeInterface, string, or null.',
            );
        }

        match ($method) {
            'where' => $query->getQuery()->whereDate($column, $value),
            'orWhere' => $query->getQuery()->orWhereDate($column, $value),
            default => throw new InvalidRequestQueryException(sprintf(
                'Request query method [%s] is not supported for date. Supported methods: where, orWhere.',
                $method,
            )),
        };
    }

    /**
     * @param  Builder<*>  $query
     *
     * @throws InvalidRequestQueryException
     */
    private static function callJsonContains(Builder $query, string $method, string $column, mixed $value): void
    {
        match ($method) {
            'where' => $query->getQuery()->whereJsonContains($column, $value),
            'orWhere' => $query->getQuery()->orWhereJsonContains($column, $value),
            default => throw new InvalidRequestQueryException(sprintf(
                'Request query method [%s] is not supported for jsonContains. Supported methods: where, orWhere.',
                $method,
            )),
        };
    }

    /**
     * @throws InvalidRequestQueryException
     */
    private static function wrapValue(mixed $value, string $prefix, string $suffix): string
    {
        if ($value !== null && ! is_scalar($value)) {
            throw new InvalidRequestQueryException(
                'Request query operator value must be a scalar or null for pattern matching.',
            );
        }

        return $prefix . (string) $value . $suffix;
    }
}
