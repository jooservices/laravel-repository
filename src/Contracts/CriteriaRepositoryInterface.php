<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface CriteriaRepositoryInterface
{
    public function pushCriteria(CriteriaInterface $criteria): static;

    public function popCriteria(): ?CriteriaInterface;

    public function clearCriteria(): static;

    /**
     * @return list<CriteriaInterface>
     */
    public function criteria(): array;

    /**
     * @param  Builder<*>  $query
     */
    public function applyCriteria(Builder $query): void;
}
