<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

trait HasRead
{
    public function first(): ?Model
    {
        try {
            return $this->getQuery()->first();
        } finally {
            $this->query = null;
        }
    }

    /**
     * @throws ModelNotFoundException
     */
    public function firstOrFail(): Model
    {
        try {
            return $this->getQuery()->firstOrFail();
        } finally {
            $this->query = null;
        }
    }

    public function exists(): bool
    {
        try {
            return $this->getQuery()->toBase()->exists();
        } finally {
            $this->query = null;
        }
    }

    public function count(): int
    {
        try {
            return $this->getQuery()->toBase()->count();
        } finally {
            $this->query = null;
        }
    }

    public function value(string $column): mixed
    {
        try {
            return $this->getQuery()->value($column);
        } finally {
            $this->query = null;
        }
    }

    /**
     * @return Collection<int, mixed>
     */
    public function pluck(string $column, ?string $key = null): Collection
    {
        try {
            return $this->getQuery()->pluck($column, $key);
        } finally {
            $this->query = null;
        }
    }

    public function sum(string $column): mixed
    {
        try {
            return $this->getQuery()->toBase()->sum($column);
        } finally {
            $this->query = null;
        }
    }

    public function avg(string $column): mixed
    {
        try {
            return $this->getQuery()->toBase()->avg($column);
        } finally {
            $this->query = null;
        }
    }

    public function min(string $column): mixed
    {
        try {
            return $this->getQuery()->toBase()->min($column);
        } finally {
            $this->query = null;
        }
    }

    public function max(string $column): mixed
    {
        try {
            return $this->getQuery()->toBase()->max($column);
        } finally {
            $this->query = null;
        }
    }
}
