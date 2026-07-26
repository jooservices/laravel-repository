<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use JOOservices\LaravelRepository\Contracts\CriteriaInterface;

class ActiveStatusCriteriaStub implements CriteriaInterface
{
    /**
     * @param  Builder<*>  $query
     */
    public function apply(Builder $query): void
    {
        $query->where('status', 'active');
    }
}
