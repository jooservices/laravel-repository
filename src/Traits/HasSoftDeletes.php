<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use RuntimeException;

/**
 * Soft-delete helpers. The bound model must use Illuminate SoftDeletes.
 *
 * @phpstan-require-extends \JOOservices\LaravelRepository\Repositories\EloquentRepository
 */
trait HasSoftDeletes
{
    /**
     * @throws RuntimeException
     */
    public function withTrashed(): static
    {
        $this->assertSoftDeletes();
        $this->getQuery()->withoutGlobalScope(SoftDeletingScope::class);

        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function onlyTrashed(): static
    {
        $this->assertSoftDeletes();

        $column = $this->deletedAtColumn();
        $this->getQuery()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->getQuery()
            ->whereNotNull($column);

        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function withoutTrashed(): static
    {
        $this->assertSoftDeletes();

        $column = $this->deletedAtColumn();
        $this->getQuery()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->getQuery()
            ->whereNull($column);

        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function restore(int | string $id): bool
    {
        $this->assertSoftDeletes();

        $model = $this->newQueryWithCriteria()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->findOrFail($id);

        if (! method_exists($model, 'restore')) {
            throw new RuntimeException(sprintf(
                'Model [%s] does not define restore().',
                $model::class,
            ));
        }

        $restore = [$model, 'restore'];
        if (! is_callable($restore)) {
            throw new RuntimeException(sprintf(
                'Model [%s] restore() is not callable.',
                $model::class,
            ));
        }

        return (bool) $restore();
    }

    /**
     * @throws RuntimeException
     */
    public function forceDelete(int | string $id): bool
    {
        $this->assertSoftDeletes();

        $model = $this->newQueryWithCriteria()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->findOrFail($id);

        if (! method_exists($model, 'forceDelete')) {
            throw new RuntimeException(sprintf(
                'Model [%s] does not define forceDelete().',
                $model::class,
            ));
        }

        $forceDelete = [$model, 'forceDelete'];
        if (! is_callable($forceDelete)) {
            throw new RuntimeException(sprintf(
                'Model [%s] forceDelete() is not callable.',
                $model::class,
            ));
        }

        return (bool) $forceDelete();
    }

    /**
     * @throws RuntimeException
     */
    private function assertSoftDeletes(): void
    {
        $uses = class_uses_recursive($this->getModel());

        if (! in_array(SoftDeletes::class, $uses, true)) {
            throw new RuntimeException(sprintf(
                'Model [%s] must use %s to use soft-delete repository helpers.',
                $this->getModel()::class,
                SoftDeletes::class,
            ));
        }
    }

    /**
     * @throws RuntimeException
     */
    private function deletedAtColumn(): string
    {
        $model = $this->getModel();

        if (! method_exists($model, 'getDeletedAtColumn')) {
            throw new RuntimeException(sprintf(
                'Model [%s] does not define getDeletedAtColumn().',
                $model::class,
            ));
        }

        $column = $model->getDeletedAtColumn();
        if (! is_string($column) || $column === '') {
            throw new RuntimeException(sprintf(
                'Model [%s] getDeletedAtColumn() must return a non-empty string.',
                $model::class,
            ));
        }

        return $column;
    }
}
