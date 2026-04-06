<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Jooservices\LaravelRepository\Support\Order;

interface OrderableRepositoryInterface
{
    /**
     * @param  iterable<int|string, Order|'asc'|'desc'>  $orders
     */
    public function orderBy(iterable $orders): static;
}
