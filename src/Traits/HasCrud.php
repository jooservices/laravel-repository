<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait HasCrud
{
    public function find(int|string $id): ?Model
    {
        return $this->crudQuery()->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->crudQuery()->findOrFail($id);
    }

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return $this->crudQuery()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Model>
     */
    private function crudQuery(): \Illuminate\Database\Eloquent\Builder
    {
        if (method_exists($this, 'newQueryWithCriteria')) {
            return $this->newQueryWithCriteria();
        }

        return $this->getModel()->newQuery();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->getModel()->newQuery()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): bool
    {
        $model = $this->findOrFail($id);

        return $model->update($data);
    }

    public function delete(int|string $id): bool
    {
        $model = $this->findOrFail($id);

        return (bool) $model->delete();
    }
}
