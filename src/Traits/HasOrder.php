<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use InvalidArgumentException;
use JOOservices\LaravelRepository\Support\Order;

trait HasOrder
{
    private const ORDER_DIRECTIONS = ['asc', 'desc'];

    /**
     * @param  iterable<int|string|Order, Order|'asc'|'desc'|string>  $orders
     *
     * @throws InvalidArgumentException
     */
    public function orderBy(iterable $orders): static
    {
        // Mutate the retained Eloquent builder's base query (not toBase(), which
        // clones when global scopes exist and discards mutations).
        $query = $this->getQuery()->getQuery();
        foreach ($orders as $column => $direction) {
            if ($column instanceof Order) {
                $query->orderBy($column->column, $this->normalizeDirection($column->direction));

                continue;
            }

            if ($direction instanceof Order) {
                $query->orderBy($direction->column, $this->normalizeDirection($direction->direction));

                continue;
            }

            if (is_int($column) && is_string($direction)) {
                foreach ($this->parseOrderShorthand($direction) as $order) {
                    $query->orderBy($order['column'], $order['direction']);
                }

                continue;
            }

            if (is_string($column) && str_starts_with($column, '-')) {
                $parsed = $this->parseOrderShorthand($column)[0] ?? null;
                if ($parsed !== null) {
                    $query->orderBy($parsed['column'], $parsed['direction']);
                }

                continue;
            }

            $query->orderBy((string) $column, $this->normalizeDirection($direction));
        }

        return $this;
    }

    public function clearOrders(): static
    {
        $this->getQuery()->getQuery()->reorder();

        return $this;
    }

    /**
     * @return list<array{column: string, direction: 'asc'|'desc'}>
     */
    private function parseOrderShorthand(string $value): array
    {
        $result = [];

        foreach (explode(',', $value) as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (str_starts_with($segment, '-')) {
                $column = trim(substr($segment, 1));
                if ($column !== '') {
                    $result[] = ['column' => $column, 'direction' => 'desc'];
                }

                continue;
            }

            $result[] = ['column' => $segment, 'direction' => 'asc'];
        }

        return $result;
    }

    /**
     * @return 'asc'|'desc'
     */
    private function normalizeDirection(mixed $direction): string
    {
        if (! is_string($direction)) {
            return 'asc';
        }

        $normalized = strtolower(trim($direction));
        if (! in_array($normalized, self::ORDER_DIRECTIONS, true)) {
            return 'asc';
        }

        return $normalized === 'desc' ? 'desc' : 'asc';
    }
}
