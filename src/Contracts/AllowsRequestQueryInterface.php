<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

interface AllowsRequestQueryInterface
{
    /**
     * @return list<string>|null
     */
    public function allowedFilters(): ?array;

    /**
     * @return list<string>|null
     */
    public function allowedSorts(): ?array;

    /**
     * @return list<string>|null
     */
    public function allowedIncludes(): ?array;

    /**
     * @return list<string>|null
     */
    public function allowedFields(): ?array;

    /**
     * @return list<string>|null
     */
    public function allowedScopes(): ?array;

    /**
     * @return array<string, list<string>>|null
     */
    public function allowedRelationFilters(): ?array;

    public function isRequestQueryStrict(): bool;
}
