<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

interface OrderableRepositoryInterface
{
    /**
     * @param  iterable<string, 'asc'|'desc'>|iterable  $orders
     */
    public function orderBy(iterable $orders): static;
}
