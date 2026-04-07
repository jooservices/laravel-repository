<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Jooservices\LaravelRepository\Support\Order;

trait HasOrder
{
    private const ORDER_DIRECTIONS = ['asc', 'desc'];

    /**
     * @param  iterable<int|string|Order, Order|'asc'|'desc'>  $orders
     */
    public function orderBy(iterable $orders): static
    {
        $query = $this->getQuery();
        foreach ($orders as $column => $direction) {
            if ($column instanceof Order) {
                $query->orderBy($column->column, $this->normalizeDirection($column->direction));
            } elseif ($direction instanceof Order) {
                $query->orderBy($direction->column, $this->normalizeDirection($direction->direction));
            } else {
                $query->orderBy((string) $column, $this->normalizeDirection($direction));
            }
        }

        return $this;
    }

    private function normalizeDirection(mixed $direction): string
    {
        if (! is_string($direction)) {
            return 'asc';
        }

        $normalized = strtolower(trim($direction));

        return in_array($normalized, self::ORDER_DIRECTIONS, true) ? $normalized : 'asc';
    }
}
