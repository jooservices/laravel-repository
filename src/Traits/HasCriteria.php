<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\Builder;
use Jooservices\LaravelRepository\Contracts\CriteriaInterface;

trait HasCriteria
{
    /**
     * @var list<CriteriaInterface>
     */
    protected array $criteria = [];

    private ?int $criteriaQueryId = null;

    public function pushCriteria(CriteriaInterface $criteria): static
    {
        $this->criteria[] = $criteria;

        if ($this->query !== null) {
            $criteria->apply($this->query);
            $this->criteriaQueryId = spl_object_id($this->query);
        }

        return $this;
    }

    public function popCriteria(): ?CriteriaInterface
    {
        $criteria = array_pop($this->criteria);
        $this->query = null;
        $this->criteriaQueryId = null;

        return $criteria;
    }

    public function clearCriteria(): static
    {
        $this->criteria = [];
        $this->query = null;
        $this->criteriaQueryId = null;

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

        $queryId = spl_object_id($query);
        if ($this->criteriaQueryId === $queryId) {
            return;
        }

        foreach ($this->criteria as $criteria) {
            $criteria->apply($query);
        }

        $this->criteriaQueryId = $queryId;
    }
}
