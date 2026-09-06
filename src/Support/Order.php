<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Support;

readonly class Order
{
    public function __construct(
        public string $column,
        public string $direction = 'asc',
    ) {
    }
}
