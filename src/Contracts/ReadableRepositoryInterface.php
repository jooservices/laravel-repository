<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface ReadableRepositoryInterface
{
    public function first(): ?Model;

    public function firstOrFail(): Model;

    public function exists(): bool;

    public function count(): int;

    public function value(string $column): mixed;

    /**
     * @return Collection<int, mixed>
     */
    public function pluck(string $column, ?string $key = null): Collection;

    public function sum(string $column): mixed;

    public function avg(string $column): mixed;

    public function min(string $column): mixed;

    public function max(string $column): mixed;
}
