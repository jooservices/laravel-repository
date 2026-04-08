<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

/**
 * @phpstan-import-type WhereClause from RequestQueryParser
 * @phpstan-import-type WhereInClause from RequestQueryParser
 * @phpstan-import-type WhereBetweenClause from RequestQueryParser
 * @phpstan-import-type WhereHasClause from RequestQueryParser
 */
class RequestQueryRelationParser
{
    /**
     * @param  array<int|string, mixed>  $items
     * @return list<WhereHasClause>
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
     * @return WhereHasClause|null
     */
    private static function parseItem(mixed $item): ?array
    {
        $result = null;

        if (! is_array($item)) {
            return $result;
        }

        $relation = $item['relation'] ?? $item[0] ?? null;
        if (! is_string($relation)) {
            return $result;
        }

        $relation = trim($relation);
        if ($relation !== '') {
            $result = [
                'relation' => $relation,
                'where' => RequestQueryParser::parseWhere(self::arrayClause($item, 'where')),
                'orWhere' => RequestQueryParser::parseWhere(self::arrayClause($item, 'orWhere')),
                'whereIn' => RequestQueryParser::parseWhereIn(self::arrayClause($item, 'whereIn')),
                'whereBetween' => RequestQueryParser::parseWhereBetween(self::arrayClause($item, 'whereBetween')),
                'whereNull' => RequestQueryParser::parseWhereNull(self::arrayClause($item, 'whereNull')),
                'whereNotNull' => RequestQueryParser::parseWhereNotNull(self::arrayClause($item, 'whereNotNull')),
            ];
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $item
     * @return array<int|string, mixed>
     */
    private static function arrayClause(array $item, string $key): array
    {
        return is_array($item[$key] ?? null) ? $item[$key] : [];
    }
}
