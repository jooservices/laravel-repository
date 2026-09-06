<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use JOOservices\LaravelRepository\Contracts\RequestFilterInterface;

/**
 * Named filter that adds a join (must run on the outer query builder).
 */
class PeerNameRequestFilterStub implements RequestFilterInterface
{
    /**
     * @param  Builder<*>  $query
     */
    public function apply(Builder $query, mixed $value): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $query->getQuery()->join('users as peer', 'peer.id', '=', 'users.id');
        $query->where('peer.name', '=', $value);
    }
}
