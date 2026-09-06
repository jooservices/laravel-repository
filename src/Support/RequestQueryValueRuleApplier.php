<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Support;

/**
 * @internal
 */
final class RequestQueryValueRuleApplier
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public static function apply(mixed $value, string $rule, array $arguments): mixed
    {
        return match (strtolower($rule)) {
            'trim' => self::mapRecursive(
                $value,
                static fn(mixed $item): mixed => is_string($item) ? trim($item) : $item,
            ),
            'lower', 'lowercase' => self::mapRecursive(
                $value,
                static fn(mixed $item): mixed => is_string($item) ? mb_strtolower($item) : $item,
            ),
            'upper', 'uppercase' => self::mapRecursive(
                $value,
                static fn(mixed $item): mixed => is_string($item) ? mb_strtoupper($item) : $item,
            ),
            'string' => self::mapRecursive(
                $value,
                static fn(mixed $item): mixed => is_scalar($item) ? (string) $item : $item,
            ),
            'int', 'integer' => self::mapRecursive(
                $value,
                static fn(mixed $item): mixed => is_numeric($item) ? (int) $item : $item,
            ),
            'float', 'double', 'decimal' => self::mapRecursive(
                $value,
                static fn(mixed $item): mixed => is_numeric($item) ? (float) $item : $item,
            ),
            'bool', 'boolean' => self::mapRecursive(
                $value,
                static fn(mixed $item): mixed => self::normalizeBoolean($item),
            ),
            'array' => is_array($value) ? array_values($value) : [$value],
            'csv' => self::normalizeCsv($value, $arguments),
            'unique' => is_array($value) ? array_values(array_unique($value, SORT_REGULAR)) : $value,
            'null_if_empty', 'nullable' => self::nullIfEmpty($value),
            'null_if_literal' => self::nullIfLiteral($value),
            'ignore' => self::applyIgnore($value, $arguments),
            'default' => self::applyDefault($value, $arguments),
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private static function applyIgnore(mixed $value, array $arguments): mixed
    {
        $ignored = $arguments['values'] ?? $arguments['value'] ?? ['', '*', 'all'];
        if (! is_array($ignored)) {
            $ignored = [$ignored];
        }

        $compare = is_string($value) ? trim($value) : $value;

        foreach ($ignored as $candidate) {
            if ($compare === $candidate) {
                return SkippedRequestValue::instance();
            }
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private static function applyDefault(mixed $value, array $arguments): mixed
    {
        if (! array_key_exists('value', $arguments)) {
            return $value;
        }

        if ($value === null) {
            return $arguments['value'];
        }

        if (is_string($value) && trim($value) === '') {
            return $arguments['value'];
        }

        if (is_array($value) && $value === []) {
            return $arguments['value'];
        }

        return $value;
    }

    /**
     * @param  callable(mixed): mixed  $callback
     */
    private static function mapRecursive(mixed $value, callable $callback): mixed
    {
        if (! is_array($value)) {
            return $callback($value);
        }

        return array_map(
            static fn(mixed $item): mixed => self::mapRecursive($item, $callback),
            $value,
        );
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<mixed>
     */
    private static function normalizeCsv(mixed $value, array $arguments): array
    {
        $delimiter = ',';
        if (isset($arguments['delimiter']) && is_string($arguments['delimiter']) && $arguments['delimiter'] !== '') {
            $delimiter = $arguments['delimiter'];
        } elseif (isset($arguments['value']) && is_string($arguments['value']) && $arguments['value'] !== '') {
            $delimiter = $arguments['value'];
        }

        $items = is_array($value) ? $value : [$value];
        $normalized = [];

        foreach ($items as $item) {
            if (! is_string($item)) {
                $normalized[] = $item;

                continue;
            }

            foreach (explode($delimiter, $item) as $segment) {
                $normalized[] = trim($segment);
            }
        }

        return array_values(array_filter(
            $normalized,
            static fn(mixed $item): bool => ! (is_string($item) && $item === ''),
        ));
    }

    private static function normalizeBoolean(mixed $value): mixed
    {
        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => $value,
            };
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => $value,
            };
        }

        return $value;
    }

    private static function nullIfEmpty(mixed $value): mixed
    {
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        if (is_array($value) && $value === []) {
            return null;
        }

        return $value;
    }

    private static function nullIfLiteral(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return match (strtolower(trim($value))) {
            'null', 'nil', 'none', 'undefined' => null,
            default => $value,
        };
    }
}
