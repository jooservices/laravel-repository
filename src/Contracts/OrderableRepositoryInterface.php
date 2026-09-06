<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Contracts;

use JOOservices\LaravelRepository\Support\Order;

interface OrderableRepositoryInterface
{
    /**
     * @param  iterable<int|string|Order, Order|'asc'|'desc'|string>  $orders
     */
    public function orderBy(iterable $orders): static;

    public function clearOrders(): static;
}
