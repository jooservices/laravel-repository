<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use JOOservices\LaravelRepository\Contracts\RequestFilterInterface;

/**
 * Named filter that applies select / order / limit (non-WHERE transformations).
 */
class TopByIdRequestFilterStub implements RequestFilterInterface
{
    /**
     * @param  Builder<*>  $query
     *
     * @throws InvalidArgumentException
     */
    public function apply(Builder $query, mixed $value): void
    {
        $query->getQuery()->orderBy('id', 'desc');
        $query->getQuery()->limit(1);
        $query->getQuery()->select(['id', 'name']);
    }
}
