<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Support;

final class RequestQueryValueNormalizer
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

            $value = RequestQueryValueRuleApplier::apply($value, $rule['name'], $rule['arguments']);
        }

        return $value;
    }

    /**
     * @return array{name: string, arguments: array<string, mixed>}|null
     */
    private static function normalizeRuleDefinition(mixed $definition): ?array
    {
        if (is_string($definition)) {
            $name = trim($definition);

            return $name !== ''
                ? ['name' => $name, 'arguments' => []]
                : null;
        }

        if (! is_array($definition)) {
            return null;
        }

        if (isset($definition['rule']) && is_string($definition['rule'])) {
            $name = trim($definition['rule']);
            if ($name === '') {
                return null;
            }

            $arguments = $definition;
            unset($arguments['rule']);

            return [
                'name' => $name,
                'arguments' => self::normalizeArguments($arguments),
            ];
        }

        if ($definition === [] || ! is_string($definition[0] ?? null)) {
            return null;
        }

        $name = trim($definition[0]);
        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'arguments' => ['value' => $definition[1] ?? null],
        ];
    }

    /**
     * @param  array<mixed>  $arguments
     * @return array<string, mixed>
     */
    private static function normalizeArguments(array $arguments): array
    {
        $normalized = [];

        foreach ($arguments as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
