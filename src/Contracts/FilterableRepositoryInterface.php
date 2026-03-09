<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface FilterableRepositoryInterface
{
    /**
     * @param  iterable<string, mixed>|iterable<FilterInterface>  $filters
     */
    public function filter(iterable $filters): static;

    public function get(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function newQuery(): Builder;
}
