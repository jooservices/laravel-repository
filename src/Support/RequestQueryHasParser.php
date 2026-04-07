<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

/**
 * @phpstan-type HasClause array{relation: string, operator: string, count: int}
 */
class RequestQueryHasParser
{
    /**
     * @param  array<int|string, mixed>  $items
     * @return list<HasClause>
     */
    public static function parse(array $items): array
    {
        $result = [];

        foreach ($items as $index => $item) {
            $parsed = self::parseItem($index, $item);
            if ($parsed !== null) {
                $result[] = $parsed;
            }
        }

        return $result;
    }

    /**
     * @return HasClause|null
     */
    private static function parseItem(int|string $index, mixed $item): ?array
    {
        $clause = null;

        if (! is_array($item)) {
            return $clause;
        }

        if (isset($item['relation']) && is_string($item['relation'])) {
            $clause = self::buildClause(
                $item['relation'],
                $item['operator'] ?? '>=',
                $item['count'] ?? $item['value'] ?? 1,
            );
        } elseif (is_int($index) && count($item) >= 2) {
            $relation = $item[0] ?? null;
            if (is_string($relation)) {
                $clause = count($item) >= 3
                    ? self::buildClause($relation, $item[1] ?? '>=', $item[2] ?? 1)
                    : self::buildClause($relation, '>=', $item[1] ?? 1);
            }
        }

        return $clause;
    }

    /**
     * @return HasClause
     */
    private static function buildClause(string $relation, mixed $operator, mixed $count): array
    {
        $relation = trim($relation);
        $operator = is_string($operator) ? trim($operator) : '>=';

        if (! in_array($operator, ['=', '<', '>', '<=', '>=', '!='], true)) {
            $operator = '>=';
        }

        $count = is_numeric($count) ? max(0, (int) $count) : 1;

        return [
            'relation' => $relation,
            'operator' => $operator,
            'count' => $count,
        ];
    }
}
