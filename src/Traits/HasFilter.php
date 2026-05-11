<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Jooservices\LaravelRepository\Contracts\FilterInterface;

trait HasFilter
{
    /**
     * @param  iterable<string, mixed>|iterable<FilterInterface>  $filters
     */
    public function filter(iterable $filters): static
    {
        $query = $this->getQuery();
        foreach ($filters as $key => $value) {
            if ($value instanceof FilterInterface) {
                $value->apply($query);
            } else {
                $query->where($key, $value);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Model>
     */
    public function get(): Collection
    {
        try {
            return $this->getQuery()->get();
        } finally {
            $this->query = null;
        }
    }

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->getQuery()->paginate($perPage);
        } finally {
            $this->query = null;
        }
    }
}
