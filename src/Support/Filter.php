<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Support;

use Illuminate\Database\Eloquent\Builder;
use JOOservices\LaravelRepository\Contracts\FilterInterface;
use JOOservices\LaravelRepository\Exceptions\InvalidRequestQueryException;

readonly class Filter implements FilterInterface
{
    public function __construct(
        public string $field,
        public mixed $value,
        public string $operator = '=',
    ) {
    }

    /**
     * @param  Builder<*>  $query
     *
     * @throws InvalidRequestQueryException
     */
    public function apply(Builder $query): void
    {
        QueryOperator::apply($query, 'where', $this->field, $this->operator, $this->value);
    }
}
