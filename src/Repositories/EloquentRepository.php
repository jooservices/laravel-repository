<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JOOservices\LaravelRepository\Contracts\CriteriaRepositoryInterface;

class EloquentRepository
{
    /**
     * @var Builder<Model>|null
     */
    protected ?Builder $query = null;

    public function __construct(
        protected Model $model,
    ) {}

    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * @return Builder<Model>
     */
    public function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Fresh builder with repository-wide criteria applied, without mutable
     * filter or ordering state from the current fluent query chain.
     *
     * Override this hook to customize criteria-aware CRUD reads.
     *
     * Criteria are applied directly (not via applyCriteria idempotency) because
     * this always builds a brand-new builder. applyCriteria tracks object ids of
     * the shared fluent builder; reusing those ids after GC can skip criteria.
     *
     * @return Builder<Model>
     */
    protected function newQueryWithCriteria(): Builder
    {
        $query = $this->newQuery();

        if ($this instanceof CriteriaRepositoryInterface) {
            foreach ($this->criteria() as $criteria) {
                $criteria->apply($query);
            }
        }

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    protected function getQuery(): Builder
    {
        $query = $this->query ??= $this->newQuery();

        if ($this instanceof CriteriaRepositoryInterface) {
            $this->applyCriteria($query);
        }

        return $query;
    }
}
