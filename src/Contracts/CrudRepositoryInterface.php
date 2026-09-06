<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface CrudRepositoryInterface
{
    public function find(int | string $id): ?Model;

    public function findOrFail(int | string $id): Model;

    /**
     * @param  list<int|string>  $ids
     * @return Collection<int, Model>
     */
    public function findMany(array $ids): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function findBy(array $attributes): ?Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function findByOrFail(array $attributes): Model;

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * @param  array<int, array<string, mixed>>  $values
     * @param  list<string>  $uniqueBy
     * @param  list<string>|null  $update
     */
    public function upsert(array $values, array $uniqueBy, ?array $update = null): int;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int | string $id, array $data): bool;

    public function delete(int | string $id): bool;
}
