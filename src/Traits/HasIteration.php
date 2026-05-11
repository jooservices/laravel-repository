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
        try {
            return $this->getQuery()->chunk($count, $callback);
        } finally {
            $this->query = null;
        }
    }

    /**
     * @return LazyCollection<int, Model>
     */
    public function lazy(int $chunkSize = 1000): LazyCollection
    {
        try {
            return $this->getQuery()->lazy($chunkSize);
        } finally {
            $this->query = null;
        }
    }

    /**
     * @return LazyCollection<int, Model>
     */
    public function cursor(): LazyCollection
    {
        try {
            return $this->getQuery()->cursor();
        } finally {
            $this->query = null;
        }
    }

    /**
     * @return LazyCollection<int, Model>
     */
    public function lazyById(int $chunkSize = 1000, ?string $column = null, ?string $alias = null): LazyCollection
    {
        try {
            return $this->getQuery()->lazyById($chunkSize, $column, $alias);
        } finally {
            $this->query = null;
        }
    }

    /**
     * @return LazyCollection<int, Model>
     */
    public function lazyByIdDesc(int $chunkSize = 1000, ?string $column = null, ?string $alias = null): LazyCollection
    {
        try {
            return $this->getQuery()->lazyByIdDesc($chunkSize, $column, $alias);
        } finally {
            $this->query = null;
        }
    }
}
