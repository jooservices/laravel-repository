<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

trait HasAllowedRequestQuery
{
    /**
     * @var list<mixed>|null
     */
    protected ?array $allowedFilters = null;

    /**
     * @var list<mixed>|null
     */
    protected ?array $allowedSorts = null;

    /**
     * @var list<mixed>|null
     */
    protected ?array $allowedIncludes = null;

    /**
     * @var list<mixed>|null
     */
    protected ?array $allowedFields = null;

    /**
     * @var list<mixed>|null
     */
    protected ?array $allowedScopes = null;

    /**
     * @var array<string, list<mixed>>|null
     */
    protected ?array $allowedRelationFilters = null;

    protected ?bool $requestQueryStrict = null;

    /**
     * @return list<string>|null
     */
    public function allowedFilters(): ?array
    {
        return $this->normalizeAllowlist($this->allowedFilters);
    }

    /**
     * @return list<string>|null
     */
    public function allowedSorts(): ?array
    {
        return $this->normalizeAllowlist($this->allowedSorts);
    }

    /**
     * @return list<string>|null
     */
    public function allowedIncludes(): ?array
    {
        return $this->normalizeAllowlist($this->allowedIncludes);
    }

    /**
     * @return list<string>|null
     */
    public function allowedFields(): ?array
    {
        return $this->normalizeAllowlist($this->allowedFields);
    }

    /**
     * @return list<string>|null
     */
    public function allowedScopes(): ?array
    {
        return $this->normalizeAllowlist($this->allowedScopes);
    }

    /**
     * @return array<string, list<string>>|null
     */
    public function allowedRelationFilters(): ?array
    {
        if ($this->allowedRelationFilters === null) {
            return null;
        }

        $normalized = [];

        foreach ($this->allowedRelationFilters as $relation => $columns) {
            if (! is_string($relation)) {
                continue;
            }

            $relation = trim($relation);
            if ($relation === '') {
                continue;
            }

            $normalizedColumns = $this->normalizeAllowlist(is_array($columns) ? $columns : [$columns]);
            if ($normalizedColumns === null) {
                continue;
            }

            $normalized[$relation] = $normalizedColumns;
        }

        return $normalized;
    }

    public function isRequestQueryStrict(): bool
    {
        return $this->requestQueryStrict ?? (bool) config('laravel-repository.request_query.strict', false);
    }

    /**
     * @param  list<mixed>|null  $allowlist
     * @return list<string>|null
     */
    private function normalizeAllowlist(?array $allowlist): ?array
    {
        if ($allowlist === null) {
            return null;
        }

        return array_values(array_filter(
            $allowlist,
            static fn (mixed $value): bool => is_string($value) && $value !== '',
        ));
    }
}
