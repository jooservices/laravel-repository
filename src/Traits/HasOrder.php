<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Jooservices\LaravelRepository\Support\Order;

trait HasOrder
{
    /**
     * @param  iterable<int|string, Order|'asc'|'desc'>  $orders
     */
    public function orderBy(iterable $orders): static
    {
        $query = $this->getQuery();
        foreach ($orders as $column => $direction) {
            if ($column instanceof Order) {
                $query->orderBy($column->column, $column->direction);
            } elseif ($direction instanceof Order) {
                $query->orderBy($direction->column, $direction->direction);
            } else {
                $query->orderBy((string) $column, is_string($direction) ? $direction : 'asc');
            }
        }

        return $this;
    }
}
