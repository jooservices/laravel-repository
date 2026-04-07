<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

trait HasCursorPagination
{
    /**
     * @param  array<int, string>|string  $columns
     * @return CursorPaginator<int, object>
     */
    public function cursorPaginate(
        int $perPage = 15,
        array|string $columns = ['*'],
        string $cursorName = 'cursor',
        ?string $cursor = null,
    ): CursorPaginator {
        $query = $this->getQuery();
        $this->ensureCursorPaginationOrder($query);

        $result = $query->cursorPaginate($perPage, $columns, $cursorName, $cursor);
        $this->query = null;

        return $result;
    }

    /**
     * @param  Builder<*>  $query
     */
    private function ensureCursorPaginationOrder(Builder $query): void
    {
        if ($query->getQuery()->orders !== null && $query->getQuery()->orders !== []) {
            return;
        }

        $model = $this->getModel();
        $query->orderBy($model->qualifyColumn($model->getKeyName()), 'asc');
    }
}
