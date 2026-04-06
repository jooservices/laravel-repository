<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FilterInterface
{
    /**
     * @param  Builder<*>  $query
     */
    public function apply(Builder $query): void;
}
