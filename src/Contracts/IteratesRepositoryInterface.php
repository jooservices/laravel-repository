<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

interface IteratesRepositoryInterface
{
    /**
     * @param  Closure(\Illuminate\Support\Collection<int, Model>, int): mixed  $callback
     */
    public function chunk(int $count, Closure $callback): bool;

    /**
     * @return LazyCollection<int, Model>
     */
    public function lazy(int $chunkSize = 1000): LazyCollection;

    /**
     * @return LazyCollection<int, Model>
     */
    public function cursor(): LazyCollection;

    /**
     * @return LazyCollection<int, Model>
     */
    public function lazyById(int $chunkSize = 1000, ?string $column = null, ?string $alias = null): LazyCollection;

    /**
     * @return LazyCollection<int, Model>
     */
    public function lazyByIdDesc(int $chunkSize = 1000, ?string $column = null, ?string $alias = null): LazyCollection;
}
