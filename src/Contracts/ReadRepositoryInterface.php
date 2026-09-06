<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Contracts;

/**
 * Read-only repository surface (filter, order, read, request-query).
 *
 * Does not include CRUD. Pair with {@see AllowsRequestQueryInterface} when
 * allowlists / strict mode are required.
 */
interface ReadRepositoryInterface extends
    FilterableRepositoryInterface,
    OrderableRepositoryInterface,
    ReadableRepositoryInterface,
    RequestQueryRepositoryInterface
{
}
