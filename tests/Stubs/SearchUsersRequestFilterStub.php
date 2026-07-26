<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use JOOservices\LaravelRepository\Contracts\RequestFilterInterface;
use JOOservices\LaravelRepository\Support\QueryOperator;

class SearchUsersRequestFilterStub implements RequestFilterInterface
{
    /**
     * @param  Builder<*>  $query
     */
    public function apply(Builder $query, mixed $value): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($value): void {
            QueryOperator::apply($builder, 'where', 'name', 'partial', $value);
            QueryOperator::apply($builder, 'orWhere', 'email', 'partial', $value);
        });
    }
}
