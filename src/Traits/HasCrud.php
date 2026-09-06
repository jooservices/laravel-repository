<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use JOOservices\LaravelRepository\Contracts\CriteriaRepositoryInterface;
use JOOservices\LaravelRepository\Exceptions\RepositoryException;
use LogicException;

/**
 * @phpstan-require-extends \JOOservices\LaravelRepository\Repositories\EloquentRepository
 */
trait HasCrud
{
    public function find(int | string $id): ?Model
    {
        return $this->crudQuery()->find($id);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFail(int | string $id): Model
    {
        return $this->crudQuery()->findOrFail($id);
    }

    /**
     * @param  list<int|string>  $ids
     * @return Collection<int, Model>
     */
    public function findMany(array $ids): Collection
    {
        return $this->crudQuery()->findMany($ids);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function findBy(array $attributes): ?Model
    {
        $query = $this->crudQuery();

        foreach ($attributes as $column => $value) {
            if (is_string($column)) {
                $query->where($column, $value);
            }
        }

        return $query->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ModelNotFoundException
     */
    public function findByOrFail(array $attributes): Model
    {
        $query = $this->crudQuery();

        foreach ($attributes as $column => $value) {
            if (is_string($column)) {
                $query->where($column, $value);
            }
        }

        return $query->firstOrFail();
    }

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return $this->crudQuery()->get();
    }

    /**
     * @return Builder<Model>
     */
    private function crudQuery(): Builder
    {
        return $this->newQueryWithCriteria();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->getModel()->newQuery()->create($data);
    }

    /**
     * Criteria-aware update-or-create: match attributes only within the
     * current repository criteria scope.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->crudQuery()->updateOrCreate($attributes, $values);
    }

    /**
     * Bulk upsert cannot safely apply repository criteria (INSERT … ON CONFLICT
     * has no WHERE criteria surface). Use only when {@see criteria()} is empty,
     * or prefer {@see updateOrCreate()} for criteria-aware writes.
     *
     * @param  array<int, array<string, mixed>>  $values
     * @param  list<string>  $uniqueBy
     * @param  list<string>|null  $update
     *
     * @throws RepositoryException when criteria are active
     */
    public function upsert(array $values, array $uniqueBy, ?array $update = null): int
    {
        if ($this instanceof CriteriaRepositoryInterface && $this->criteria() !== []) {
            throw new RepositoryException(
                'Cannot upsert while repository criteria are active: bulk upsert cannot safely'
                . ' apply criteria. Clear criteria first, or use updateOrCreate for'
                . ' criteria-aware writes.',
            );
        }

        return $this->getModel()->newQuery()->upsert($values, $uniqueBy, $update);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(int | string $id, array $data): bool
    {
        $model = $this->findOrFail($id);

        return $model->update($data);
    }

    /**
     * @throws ModelNotFoundException
     * @throws LogicException
     */
    public function delete(int | string $id): bool
    {
        $model = $this->findOrFail($id);

        return $model->delete() ?? false;
    }
}
