<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

trait HasCache
{
    protected ?string $cacheStore = null;

    /**
     * @param  Closure(static): mixed  $resolver
     */
    public function remember(string $key, DateTimeInterface|DateInterval|int|null $ttl, Closure $resolver): mixed
    {
        return $this->cacheRepository()->remember(
            $key,
            $ttl,
            fn (): mixed => $resolver($this),
        );
    }

    /**
     * @param  Closure(static): mixed  $resolver
     */
    public function rememberForever(string $key, Closure $resolver): mixed
    {
        return $this->cacheRepository()->rememberForever(
            $key,
            fn (): mixed => $resolver($this),
        );
    }

    public function forgetCache(string $key): bool
    {
        return $this->cacheRepository()->forget($key);
    }

    private function cacheRepository(): CacheRepository
    {
        return $this->cacheStore === null
            ? Cache::store()
            : Cache::store($this->cacheStore);
    }
}
