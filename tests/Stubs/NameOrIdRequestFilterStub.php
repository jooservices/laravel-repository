<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use JOOservices\LaravelRepository\Contracts\RequestFilterInterface;

/**
 * Named filter whose orWhere would escape criteria if applied on the outer query.
 */
class NameOrIdRequestFilterStub implements RequestFilterInterface
{
    /**
     * @param  Builder<*>  $query
     */
    public function apply(Builder $query, mixed $value): void
    {
        if (! is_array($value)) {
            return;
        }

        $name = $value['name'] ?? null;
        $id = $value['id'] ?? null;
        if (! is_string($name) || (! is_int($id) && ! is_numeric($id))) {
            return;
        }

        $query->where('name', '=', $name)
            ->orWhere('id', '=', (int) $id);
    }
}
