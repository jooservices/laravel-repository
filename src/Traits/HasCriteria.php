<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\Builder;
use JOOservices\LaravelRepository\Contracts\CriteriaInterface;
use WeakMap;

/**
 * @phpstan-require-extends \JOOservices\LaravelRepository\Repositories\EloquentRepository
 */
trait HasCriteria
{
    /**
     * @var list<CriteriaInterface>
     */
    protected array $criteria = [];

    /**
     * Builders that already had criteria applied. WeakMap avoids spl_object_id
     * reuse after garbage collection skipping criteria on a new builder.
     *
     * @var WeakMap<Builder<*>, true>|null
     */
    private ?WeakMap $criteriaMap = null;

    public function pushCriteria(CriteriaInterface $criteria): static
    {
        $this->criteria[] = $criteria;

        if ($this->query !== null) {
            $criteria->apply($this->query);
            $this->criteriaMap()->offsetSet($this->query, true);
        }

        return $this;
    }

    public function popCriteria(): ?CriteriaInterface
    {
        $criteria = array_pop($this->criteria);
        $this->query = null;
        $this->criteriaMap = new WeakMap();

        return $criteria;
    }

    public function clearCriteria(): static
    {
        $this->criteria = [];
        $this->query = null;
        $this->criteriaMap = new WeakMap();

        return $this;
    }

    /**
     * @return list<CriteriaInterface>
     */
    public function criteria(): array
    {
        return array_values($this->criteria);
    }

    /**
     * @param  Builder<*>  $query
     */
    public function applyCriteria(Builder $query): void
    {
        if ($this->criteria === []) {
            return;
        }

        $applied = $this->criteriaMap();
        if ($applied->offsetExists($query)) {
            return;
        }

        foreach ($this->criteria as $criteria) {
            $criteria->apply($query);
        }

        $applied->offsetSet($query, true);
    }

    /**
     * @return WeakMap<Builder<*>, true>
     */
    private function criteriaMap(): WeakMap
    {
        return $this->criteriaMap ??= new WeakMap();
    }
}
