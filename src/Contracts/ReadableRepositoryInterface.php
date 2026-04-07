<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ReadableRepositoryInterface
{
    public function first(): ?Model;

    public function firstOrFail(): Model;

    public function exists(): bool;

    public function count(): int;
}
