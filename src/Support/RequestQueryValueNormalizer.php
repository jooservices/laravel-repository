<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

class RequestQueryValueNormalizer
{
    /**
     * @param  list<mixed>  $rules
     */
    public static function normalize(mixed $value, array $rules): mixed
    {
        foreach ($rules as $ruleDefinition) {
            $rule = self::normalizeRuleDefinition($ruleDefinition);
            if ($rule === null) {
                continue;
            }

            $value = self::applyRule($value, $rule['name'], $rule['arguments']);
        }

        return $value;
    }

    /**
     * @return array{name: string, arguments: array<string, mixed>}|null
     */
    private static function normalizeRuleDefinition(mixed $definition): ?array
    {
        $normalized = null;

        if (is_string($definition)) {
            $name = trim($definition);

            if ($name !== '') {
                $normalized = ['name' => $name, 'arguments' => []];
            }
        } elseif (is_array($definition)) {
            if (isset($definition['rule']) && is_string($definition['rule'])) {
                $name = trim($definition['rule']);

                if ($name !== '') {
                    $arguments = $definition;
                    unset($arguments['rule']);

                    $normalized = ['name' => $name, 'arguments' => $arguments];
                }
            } elseif ($definition !== [] && is_string($definition[0] ?? null)) {
                $name = trim((string) $definition[0]);

                if ($name !== '') {
                    $normalized = [
                        'name' => $name,
                        'arguments' => ['value' => $definition[1] ?? null],
                    ];
                }
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private static function applyRule(mixed $value, string $rule, array $arguments): mixed
    {
        return match (strtolower($rule)) {
            'trim' => self::mapRecursive(
                $value,
                static fn (mixed $item): mixed => is_string($item) ? trim($item) : $item,
            ),
            'lower', 'lowercase' => self::mapRecursive(
                $value,
                static fn (mixed $item): mixed => is_string($item) ? mb_strtolower($item) : $item,
            ),
            'upper', 'uppercase' => self::mapRecursive(
                $value,
                static fn (mixed $item): mixed => is_string($item) ? mb_strtoupper($item) : $item,
            ),
            'string' => self::mapRecursive(
                $value,
                static fn (mixed $item): mixed => is_scalar($item) ? (string) $item : $item,
            ),
            'int', 'integer' => self::mapRecursive(
                $value,
                static fn (mixed $item): mixed => is_numeric($item) ? (int) $item : $item,
            ),
            'float', 'double', 'decimal' => self::mapRecursive(
                $value,
                static fn (mixed $item): mixed => is_numeric($item) ? (float) $item : $item,
            ),
            'bool', 'boolean' => self::mapRecursive(
                $value,
                static fn (mixed $item): mixed => self::normalizeBoolean($item),
            ),
            'array' => is_array($value) ? array_values($value) : [$value],
            'csv' => self::normalizeCsv($value, $arguments),
            'unique' => is_array($value) ? array_values(array_unique($value, SORT_REGULAR)) : $value,
            'null_if_empty' => self::nullIfEmpty($value),
            'null_if_literal' => self::nullIfLiteral($value),
            default => $value,
        };
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
            static fn (mixed $item): mixed => self::mapRecursive($item, $callback),
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
            static fn (mixed $item): bool => ! (is_string($item) && $item === ''),
        ));
    }

    private static function normalizeBoolean(mixed $value): mixed
    {
        $normalized = $value;

        if (is_string($value)) {
            $normalized = match (strtolower(trim($value))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => $value,
            };
        } elseif (is_int($value)) {
            $normalized = match ($value) {
                1 => true,
                0 => false,
                default => $value,
            };
        }

        return $normalized;
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
