<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

/**
 * Pessimistic locking helpers for the current fluent query.
 *
 * @phpstan-require-extends \JOOservices\LaravelRepository\Repositories\EloquentRepository
 */
trait HasLocking
{
    public function lockForUpdate(): static
    {
        // Use getQuery() (retained base), not toBase() (may clone under scopes).
        $this->getQuery()->getQuery()->lockForUpdate();

        return $this;
    }

    public function sharedLock(): static
    {
        $this->getQuery()->getQuery()->sharedLock();

        return $this;
    }
}
