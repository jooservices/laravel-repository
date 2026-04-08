<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

trait HasIteration
{
    /**
     * @param  Closure(Collection<int, Model>, int): mixed  $callback
     */
    public function chunk(int $count, Closure $callback): bool
    {
        $result = $this->getQuery()->chunk($count, $callback);
        $this->query = null;

        return $result;
    }

    /**
     * @return LazyCollection<int, Model>
     */
    public function lazy(int $chunkSize = 1000): LazyCollection
    {
        $result = $this->getQuery()->lazy($chunkSize);
        $this->query = null;

        return $result;
    }

    /**
     * @return LazyCollection<int, Model>
     */
    public function cursor(): LazyCollection
    {
        $result = $this->getQuery()->cursor();
        $this->query = null;

        return $result;
    }

    /**
     * @return LazyCollection<int, Model>
     */
    public function lazyById(int $chunkSize = 1000, ?string $column = null, ?string $alias = null): LazyCollection
    {
        $result = $this->getQuery()->lazyById($chunkSize, $column, $alias);
        $this->query = null;

        return $result;
    }

    /**
     * @return LazyCollection<int, Model>
     */
    public function lazyByIdDesc(int $chunkSize = 1000, ?string $column = null, ?string $alias = null): LazyCollection
    {
        $result = $this->getQuery()->lazyByIdDesc($chunkSize, $column, $alias);
        $this->query = null;

        return $result;
    }
}
