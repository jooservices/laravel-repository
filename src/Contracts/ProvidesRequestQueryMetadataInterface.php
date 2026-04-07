<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

interface ProvidesRequestQueryMetadataInterface
{
    /**
     * @return array<string, string>
     */
    public function filterAliases(): array;

    /**
     * @return array<string, string>
     */
    public function relationAliases(): array;

    /**
     * @return array<string, array{scope: string, parameters: int|null}>
     */
    public function scopeMetadata(): array;

    /**
     * @return array<string, array{relation: string, column: string, function: string, attribute: string}>
     */
    public function aggregateIncludes(): array;

    /**
     * @return array{
     *     filters: array<string, list<mixed>>,
     *     namedFilters: array<string, list<mixed>>,
     *     scopes: array<string, list<mixed>>,
     *     relations: array<string, array<string, list<mixed>>>
     * }
     */
    public function valueRules(): array;
}
