<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasRead
{
    public function first(): ?Model
    {
        $result = $this->getQuery()->first();
        $this->query = null;

        return $result;
    }

    public function firstOrFail(): Model
    {
        $result = $this->getQuery()->firstOrFail();
        $this->query = null;

        return $result;
    }

    public function exists(): bool
    {
        $result = $this->getQuery()->exists();
        $this->query = null;

        return $result;
    }

    public function count(): int
    {
        $result = $this->getQuery()->count();
        $this->query = null;

        return $result;
    }
}
