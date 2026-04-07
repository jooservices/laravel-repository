<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Illuminate\Support\Str;

trait HasRequestQueryMetadata
{
    /**
     * @var array<string, string>
     */
    protected array $filterAliases = [];

    /**
     * @var array<string, string>
     */
    protected array $relationAliases = [];

    /**
     * @var array<string, mixed>
     */
    protected array $scopeMetadata = [];

    /**
     * @var array<string, mixed>
     */
    protected array $aggregateIncludes = [];

    /**
     * @var array<string, mixed>
     */
    protected array $valueRules = [];

    /**
     * @return array<string, string>
     */
    public function filterAliases(): array
    {
        return $this->normalizeAliases($this->filterAliases);
    }

    /**
     * @return array<string, string>
     */
    public function relationAliases(): array
    {
        return $this->normalizeAliases($this->relationAliases);
    }

    /**
     * @return array<string, array{scope: string, parameters: int|null}>
     */
    public function scopeMetadata(): array
    {
        $normalized = [];

        foreach ($this->scopeMetadata as $requestName => $definition) {
            if (! is_string($requestName)) {
                continue;
            }

            $requestName = trim($requestName);
            if ($requestName === '') {
                continue;
            }

            $normalizedDefinition = $this->normalizeScopeDefinition($requestName, $definition);
            if ($normalizedDefinition === null) {
                continue;
            }

            $normalized[$requestName] = $normalizedDefinition;
        }

        return $normalized;
    }

    /**
     * @return array<string, array{relation: string, column: string, function: string, attribute: string}>
     */
    public function aggregateIncludes(): array
    {
        $normalized = [];

        foreach ($this->aggregateIncludes as $requestName => $definition) {
            if (! is_string($requestName)) {
                continue;
            }

            $requestName = trim($requestName);
            if ($requestName === '') {
                continue;
            }

            $normalizedDefinition = $this->normalizeAggregateDefinition($requestName, $definition);
            if ($normalizedDefinition === null) {
                continue;
            }

            $normalized[$requestName] = $normalizedDefinition;
        }

        return $normalized;
    }

    /**
     * @return array{
     *     filters: array<string, list<mixed>>,
     *     namedFilters: array<string, list<mixed>>,
     *     scopes: array<string, list<mixed>>,
     *     relations: array<string, array<string, list<mixed>>>
     * }
     */
    public function valueRules(): array
    {
        return [
            'filters' => $this->normalizeRuleMap($this->valueRules['filters'] ?? []),
            'namedFilters' => $this->normalizeRuleMap($this->valueRules['namedFilters'] ?? []),
            'scopes' => $this->normalizeRuleMap($this->valueRules['scopes'] ?? []),
            'relations' => $this->normalizeRelationRuleMap($this->valueRules['relations'] ?? []),
        ];
    }

    /**
     * @return array{scope: string, parameters: int|null}|null
     */
    private function normalizeScopeDefinition(string $requestName, mixed $definition): ?array
    {
        $scope = $requestName;
        $parameters = null;

        if (is_string($definition)) {
            $scope = trim($definition);
        } elseif (is_array($definition)) {
            $scope = $this->normalizeScopeName($requestName, $definition);
            $parameters = $this->normalizeScopeParameterCount($definition);
        }

        if ($scope === '') {
            return null;
        }

        return [
            'scope' => $scope,
            'parameters' => $parameters,
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function normalizeScopeName(string $requestName, array $definition): string
    {
        $scopeValue = $definition['scope'] ?? $definition['name'] ?? $requestName;

        return is_string($scopeValue) ? trim($scopeValue) : $requestName;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function normalizeScopeParameterCount(array $definition): ?int
    {
        $parameterValue = $definition['parameters'] ?? $definition['parameterCount'] ?? null;

        return is_int($parameterValue) && $parameterValue >= 0 ? $parameterValue : null;
    }

    /**
     * @param  array<string, string>  $aliases
     * @return array<string, string>
     */
    private function normalizeAliases(array $aliases): array
    {
        $normalized = [];

        foreach ($aliases as $requestName => $actualName) {
            if (! is_string($requestName) || ! is_string($actualName)) {
                continue;
            }

            $requestName = trim($requestName);
            $actualName = trim($actualName);

            if ($requestName === '' || $actualName === '') {
                continue;
            }

            $normalized[$requestName] = $actualName;
        }

        return $normalized;
    }

    /**
     * @return array{relation: string, column: string, function: string, attribute: string}|null
     */
    private function normalizeAggregateDefinition(string $requestName, mixed $definition): ?array
    {
        $normalized = null;

        if (is_array($definition)) {
            $relation = $definition['relation'] ?? null;
            $column = $definition['column'] ?? $definition['field'] ?? null;
            $function = $definition['function'] ?? $definition['aggregate'] ?? null;
            $attribute = $definition['attribute'] ?? $definition['alias'] ?? Str::snake($requestName);

            if (is_string($relation) && is_string($column) && is_string($function) && is_string($attribute)) {
                $relation = trim($relation);
                $column = trim($column);
                $function = strtolower(trim($function));
                $attribute = trim($attribute);

                if (
                    $relation !== ''
                    && $column !== ''
                    && $attribute !== ''
                    && in_array($function, ['sum', 'avg', 'min', 'max'], true)
                ) {
                    $normalized = [
                        'relation' => $relation,
                        'column' => $column,
                        'function' => $function,
                        'attribute' => $attribute,
                    ];
                }
            }
        }

        return $normalized;
    }

    /** @return array<string, list<mixed>> */
    private function normalizeRuleMap(mixed $definitions): array
    {
        if (! is_array($definitions)) {
            return [];
        }

        $normalized = [];

        foreach ($definitions as $name => $rules) {
            if (! is_string($name)) {
                continue;
            }

            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $normalizedRules = $this->normalizeRules($rules);
            if ($normalizedRules === []) {
                continue;
            }

            $normalized[$name] = $normalizedRules;
        }

        return $normalized;
    }

    /** @return array<string, array<string, list<mixed>>> */
    private function normalizeRelationRuleMap(mixed $definitions): array
    {
        if (! is_array($definitions)) {
            return [];
        }

        $normalized = [];

        foreach ($definitions as $relation => $columns) {
            if (! is_string($relation) || ! is_array($columns)) {
                continue;
            }

            $relation = trim($relation);
            if ($relation === '') {
                continue;
            }

            $normalizedColumns = $this->normalizeRuleMap($columns);
            if ($normalizedColumns === []) {
                continue;
            }

            $normalized[$relation] = $normalizedColumns;
        }

        return $normalized;
    }

    /**
     * @return list<mixed>
     */
    private function normalizeRules(mixed $rules): array
    {
        if (is_string($rules)) {
            $rules = [trim($rules)];
        }

        if (! is_array($rules)) {
            return [];
        }

        $normalized = [];

        foreach (array_values($rules) as $rule) {
            if (is_string($rule)) {
                $rule = trim($rule);
                if ($rule === '') {
                    continue;
                }
            } elseif (! is_array($rule)) {
                continue;
            }

            $normalized[] = $rule;
        }

        return $normalized;
    }
}
