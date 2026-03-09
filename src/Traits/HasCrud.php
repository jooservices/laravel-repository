<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait HasCrud
{
    public function find(int|string $id): ?Model
    {
        return $this->getModel()->newQuery()->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->getModel()->newQuery()->findOrFail($id);
    }

    public function all(): Collection
    {
        return $this->getModel()->newQuery()->get();
    }

    public function create(array $data): Model
    {
        return $this->getModel()->newQuery()->create($data);
    }

    public function update(int|string $id, array $data): bool
    {
        $model = $this->findOrFail($id);

        return $model->update($data);
    }

    public function delete(int|string $id): bool
    {
        $model = $this->findOrFail($id);

        return $model->delete();
    }
}
