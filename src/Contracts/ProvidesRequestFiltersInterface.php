<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Builder;

interface ProvidesRequestFiltersInterface
{
    /**
     * @return array<string,
     *     RequestFilterInterface|class-string<RequestFilterInterface>|Closure(Builder<*>, mixed): void
     * >
     */
    public function requestFilters(): array;
}
