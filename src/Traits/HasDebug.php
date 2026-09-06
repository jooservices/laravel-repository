<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * @phpstan-require-extends \JOOservices\LaravelRepository\Repositories\EloquentRepository
 */
trait HasDebug
{
    /**
     * @return array{sql: string, bindings: list<mixed>}
     */
    public function toQuery(): array
    {
        $query = $this->getQuery()->toBase();

        return [
            'sql' => $query->toSql(),
            'bindings' => array_values($query->getBindings()),
        ];
    }

    public function toSql(): string
    {
        return $this->getQuery()->toBase()->toSql();
    }

    /**
     * @return list<mixed>
     */
    public function getQueryBindings(): array
    {
        return array_values($this->getQuery()->toBase()->getBindings());
    }

    /**
     * Dump the current SQL + bindings and stop execution.
     *
     * @return never
     *
     * @throws RuntimeException
     */
    public function ddQuery(): never
    {
        $payload = $this->toQuery();

        if (function_exists('dd')) {
            dd($payload);
        }

        var_dump($payload);

        throw new RuntimeException('Query dump completed (dd unavailable).');
    }

    /**
     * @return Builder<*>
     */
    public function toBaseQuery(): Builder
    {
        return $this->getQuery();
    }
}
