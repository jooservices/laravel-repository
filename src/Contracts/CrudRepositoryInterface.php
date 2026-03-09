<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface CrudRepositoryInterface
{
    public function find(int|string $id): ?Model;

    public function findOrFail(int|string $id): Model;

    public function all(): Collection;

    public function create(array $data): Model;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;
}
