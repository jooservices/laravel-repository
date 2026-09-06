<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;
use RuntimeException;
use Stringable;

trait HasCache
{
    protected ?string $cacheStore = null;

    public function useCacheStore(?string $store): static
    {
        $this->cacheStore = $store;

        return $this;
    }

    /**
     * Build an unambiguous cache key. Supported part types: null, bool, int,
     * float, string, array, DateTimeInterface, Stringable, JsonSerializable.
     * Arbitrary objects are rejected (object IDs are not stable cache identity).
     *
     * @param  array<int|string, mixed>  $parts
     *
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function cacheKey(string $suffix, array $parts = []): string
    {
        $segments = [
            str_replace('\\', '.', static::class),
            trim($suffix, '.'),
        ];

        if ($parts !== []) {
            $segments[] = hash(
                'xxh128',
                json_encode($this->encodeCacheKeyParts($parts), JSON_THROW_ON_ERROR),
            );
        }

        return implode('.', array_filter($segments, static fn(string $segment): bool => $segment !== ''));
    }

    /**
     * @param  Closure(static): mixed  $resolver
     *
     * @throws BindingResolutionException
     * @throws RuntimeException
     */
    public function remember(string $key, DateTimeInterface | DateInterval | int | null $ttl, Closure $resolver): mixed
    {
        return $this->cacheRepository()->remember(
            $key,
            $ttl,
            fn(): mixed => $resolver($this),
        );
    }

    /**
     * @param  Closure(static): mixed  $resolver
     *
     * @throws BindingResolutionException
     * @throws RuntimeException
     */
    public function rememberForever(string $key, Closure $resolver): mixed
    {
        return $this->cacheRepository()->rememberForever(
            $key,
            fn(): mixed => $resolver($this),
        );
    }

    /**
     * @throws BindingResolutionException
     * @throws RuntimeException
     */
    public function forgetCache(string $key): bool
    {
        return $this->cacheRepository()->forget($key);
    }

    /**
     * Illuminate cache repository extends PSR-16 (`Psr\SimpleCache\CacheInterface`).
     * `remember` / `rememberForever` remain on the Illuminate contract.
     *
     * @throws BindingResolutionException
     * @throws RuntimeException
     */
    private function cacheRepository(): CacheRepository
    {
        $factory = app()->make(CacheFactory::class);
        if (! $factory instanceof CacheFactory) {
            throw new RuntimeException('Cache factory not bound.');
        }

        return $this->cacheStore === null
            ? $factory->store()
            : $factory->store($this->cacheStore);
    }

    /**
     * @param  array<int|string, mixed>  $parts
     * @return list<array{k: mixed, v: mixed}>
     *
     * @throws InvalidArgumentException
     */
    private function encodeCacheKeyParts(array $parts): array
    {
        $encoded = [];

        foreach ($parts as $key => $value) {
            $encoded[] = [
                'k' => $this->stringifyCacheKeyPart($key),
                'v' => $this->stringifyCacheKeyPart($value),
            ];
        }

        return $encoded;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function stringifyCacheKeyPart(mixed $value): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $this->encodeScalarCacheKeyPart($value);
        }

        if (is_array($value)) {
            return $this->encodeArrayCacheKeyPart($value);
        }

        if (is_object($value)) {
            return $this->encodeObjectCacheKeyPart($value);
        }

        throw new InvalidArgumentException(sprintf(
            'Unsupported cache key part of type [%s].',
            get_debug_type($value),
        ));
    }

    /**
     * @return array{t: 'null'|'bool'|'int'|'float'|'string', v: bool|float|int|string|null}
     */
    private function encodeScalarCacheKeyPart(bool | float | int | string | null $value): array
    {
        return match (true) {
            $value === null => ['t' => 'null', 'v' => null],
            is_bool($value) => ['t' => 'bool', 'v' => $value],
            is_int($value) => ['t' => 'int', 'v' => $value],
            is_float($value) => ['t' => 'float', 'v' => $value],
            default => ['t' => 'string', 'v' => $value],
        };
    }

    /**
     * @param  array<int|string, mixed>  $value
     * @return array{t: 'array', v: list<array{k: mixed, v: mixed}>}
     *
     * @throws InvalidArgumentException
     */
    private function encodeArrayCacheKeyPart(array $value): array
    {
        $items = [];
        foreach ($value as $key => $item) {
            $items[] = [
                'k' => $this->stringifyCacheKeyPart($key),
                'v' => $this->stringifyCacheKeyPart($item),
            ];
        }

        return ['t' => 'array', 'v' => $items];
    }

    /**
     * @return array{t: 'object', v: array{class: class-string, data: mixed}}
     *
     * @throws InvalidArgumentException
     */
    private function encodeObjectCacheKeyPart(object $value): array
    {
        if ($value instanceof DateTimeInterface) {
            return [
                't' => 'object',
                'v' => [
                    'class' => $value::class,
                    // Include microseconds; ATOM/RFC3339 drop fractional seconds.
                    'data' => $value->format('Y-m-d\\TH:i:s.uP'),
                ],
            ];
        }

        if ($value instanceof Stringable) {
            return [
                't' => 'object',
                'v' => [
                    'class' => $value::class,
                    'data' => (string) $value,
                ],
            ];
        }

        if ($value instanceof JsonSerializable) {
            return [
                't' => 'object',
                'v' => [
                    'class' => $value::class,
                    'data' => $value->jsonSerialize(),
                ],
            ];
        }

        throw new InvalidArgumentException(sprintf(
            'Unsupported cache key object [%s]. Use DateTimeInterface, Stringable, or JsonSerializable.',
            $value::class,
        ));
    }
}
