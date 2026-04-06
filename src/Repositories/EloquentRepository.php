<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
     * @return Builder<Model>
     */
    protected function getQuery(): Builder
    {
        return $this->query ??= $this->newQuery();
    }
}
