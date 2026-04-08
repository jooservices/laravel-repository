<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use Jooservices\LaravelRepository\Contracts\RequestFilterInterface;
use Jooservices\LaravelRepository\Support\QueryOperator;

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
