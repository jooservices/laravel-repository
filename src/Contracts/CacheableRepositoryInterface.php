<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Closure;
use DateInterval;
use DateTimeInterface;

interface CacheableRepositoryInterface
{
    /**
     * @param  Closure(static): mixed  $resolver
     */
    public function remember(string $key, DateTimeInterface|DateInterval|int|null $ttl, Closure $resolver): mixed;

    /**
     * @param  Closure(static): mixed  $resolver
     */
    public function rememberForever(string $key, Closure $resolver): mixed;

    public function forgetCache(string $key): bool;
}
