<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JOOservices\LaravelRepository\Contracts\FilterInterface;

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
            } elseif (is_string($key)) {
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
     *
     * @throws InvalidArgumentException
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->getQuery()->paginate($perPage);
        } finally {
            $this->query = null;
        }
    }

    /**
     * @return Paginator<int, Model>
     *
     * @throws InvalidArgumentException
     */
    public function simplePaginate(int $perPage = 15): Paginator
    {
        try {
            return $this->getQuery()->simplePaginate($perPage);
        } finally {
            $this->query = null;
        }
    }
}
