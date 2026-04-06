<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface FilterableRepositoryInterface
{
    /**
     * @param  iterable<string, mixed>|iterable<FilterInterface>  $filters
     */
    public function filter(iterable $filters): static;

    /**
     * @return Collection<int, Model>
     */
    public function get(): Collection;

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Builder<Model>
     */
    public function newQuery(): Builder;
}
