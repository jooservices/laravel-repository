<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Support;

readonly class Order
{
    public function __construct(
        public string $column,
        public string $direction = 'asc',
    ) {}
}
