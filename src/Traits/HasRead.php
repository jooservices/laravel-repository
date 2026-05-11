<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\Model;

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
            return $this->getQuery()->exists();
        } finally {
            $this->query = null;
        }
    }

    public function count(): int
    {
        try {
            return $this->getQuery()->count();
        } finally {
            $this->query = null;
        }
    }
}
