<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

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
    protected ?array $relationFilters = null;

    /**
     * Relation method paths trusted without a native Relation return type.
     * Prefer typed relation methods; use this only for legacy models.
     *
     * @var list<string>
     */
    protected array $trustedRelations = [];

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
        if ($this->relationFilters === null) {
            return null;
        }

        $normalized = [];

        foreach ($this->relationFilters as $relation => $columns) {
            if (! is_string($relation)) {
                continue;
            }

            $relation = trim($relation);
            if ($relation === '') {
                continue;
            }

            $normalizedColumns = $this->normalizeAllowlist(is_array($columns) ? $columns : [$columns]);
            if ($normalizedColumns === null || $normalizedColumns === []) {
                continue;
            }

            $normalized[$relation] = array_values($normalizedColumns);
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
            static fn(mixed $value): bool => is_string($value) && $value !== '',
        ));
    }
}
