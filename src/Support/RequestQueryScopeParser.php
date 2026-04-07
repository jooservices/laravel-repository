<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

/**
 * @phpstan-import-type ScopeClause from RequestQueryParser
 */
class RequestQueryScopeParser
{
    /**
     * @param  array<int|string, mixed>  $items
     * @return list<ScopeClause>
     */
    public static function parse(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $parsed = self::parseItem($item);
            if ($parsed !== null) {
                $result[] = $parsed;
            }
        }

        return $result;
    }

    /**
     * @return ScopeClause|null
     */
    private static function parseItem(mixed $item): ?array
    {
        if (is_string($item)) {
            $name = trim($item);

            return $name === '' ? null : ['name' => $name, 'parameters' => []];
        }

        if (! is_array($item) || $item === []) {
            return null;
        }

        $name = $item['name'] ?? $item[0] ?? null;
        if (! is_string($name)) {
            return null;
        }

        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $parameters = $item['parameters'] ?? array_slice(array_values($item), 1);

        return [
            'name' => $name,
            'parameters' => is_array($parameters) ? array_values($parameters) : [$parameters],
        ];
    }
}
