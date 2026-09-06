<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use InvalidArgumentException;

/**
 * Named allowlist profiles for request-query surfaces.
 *
 * Switching profiles restores a captured **repository baseline** (state at first
 * `withQueryProfiles()` / `forProfile()`), then overlays only keys present in
 * the named profile. Omitted keys keep the baseline — they never widen to
 * unrestricted `null` unless the baseline itself was unrestricted.
 *
 * Explicit profile values (including `strict: false` or `includes: null`) can
 * still broaden access; omit a key to preserve the baseline restriction.
 *
 * @phpstan-require-extends \JOOservices\LaravelRepository\Repositories\EloquentRepository
 *
 * @phpstan-type QueryProfile array{
 *     filters?: list<string>|null,
 *     sorts?: list<string>|null,
 *     includes?: list<string>|null,
 *     fields?: list<string>|null,
 *     scopes?: list<string>|null,
 *     relationFilters?: array<string, list<string>>|null,
 *     strict?: bool|null
 * }
 */
trait HasQueryProfiles
{
    /**
     * @var array<string, QueryProfile>
     */
    protected array $queryProfiles = [];

    /**
     * Snapshot of allowlists before any profile is applied.
     *
     * @var QueryProfile|null
     */
    private ?array $queryProfileBaseline = null;

    /**
     * Apply the named profile over the repository baseline.
     *
     * @throws InvalidArgumentException
     */
    public function forProfile(string $name): static
    {
        if (! array_key_exists($name, $this->queryProfiles)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown query profile [%s]. Known profiles: %s.',
                $name,
                $this->queryProfiles === [] ? '(none)' : implode(', ', array_keys($this->queryProfiles)),
            ));
        }

        $this->captureQueryProfileBaseline();
        $this->restoreQueryProfileBaseline();
        $this->applyProfileAllowlists($this->queryProfiles[$name]);

        return $this;
    }

    /**
     * @param  array<string, QueryProfile>  $profiles
     */
    public function withQueryProfiles(array $profiles): static
    {
        $this->captureQueryProfileBaseline();
        $this->queryProfiles = $profiles;

        return $this;
    }

    private function captureQueryProfileBaseline(): void
    {
        if ($this->queryProfileBaseline !== null) {
            return;
        }

        $this->queryProfileBaseline = [
            'filters' => $this->allowedFilters(),
            'sorts' => $this->allowedSorts(),
            'includes' => $this->allowedIncludes(),
            'fields' => $this->allowedFields(),
            'scopes' => $this->allowedScopes(),
            'relationFilters' => $this->allowedRelationFilters(),
            'strict' => $this->requestQueryStrict,
        ];
    }

    private function restoreQueryProfileBaseline(): void
    {
        $baseline = $this->queryProfileBaseline ?? [
            'filters' => null,
            'sorts' => null,
            'includes' => null,
            'fields' => null,
            'scopes' => null,
            'relationFilters' => null,
            'strict' => null,
        ];

        $this->allowedFilters = $baseline['filters'] ?? null;
        $this->allowedSorts = $baseline['sorts'] ?? null;
        $this->allowedIncludes = $baseline['includes'] ?? null;
        $this->allowedFields = $baseline['fields'] ?? null;
        $this->allowedScopes = $baseline['scopes'] ?? null;
        $this->relationFilters = $baseline['relationFilters'] ?? null;
        $this->requestQueryStrict = $baseline['strict'] ?? null;
    }

    /**
     * @param  QueryProfile  $profile
     */
    private function applyProfileAllowlists(array $profile): void
    {
        if (array_key_exists('filters', $profile)) {
            $this->allowedFilters = $profile['filters'];
        }

        if (array_key_exists('sorts', $profile)) {
            $this->allowedSorts = $profile['sorts'];
        }

        if (array_key_exists('includes', $profile)) {
            $this->allowedIncludes = $profile['includes'];
        }

        if (array_key_exists('fields', $profile)) {
            $this->allowedFields = $profile['fields'];
        }

        if (array_key_exists('scopes', $profile)) {
            $this->allowedScopes = $profile['scopes'];
        }

        if (array_key_exists('relationFilters', $profile)) {
            $this->relationFilters = $profile['relationFilters'];
        }

        if (array_key_exists('strict', $profile)) {
            $this->requestQueryStrict = $profile['strict'];
        }
    }
}
