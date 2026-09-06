<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Model;

interface CursorPaginateableRepositoryInterface
{
    /**
     * @param  array<int, string>|string  $columns
     * @return CursorPaginator<int, Model>
     */
    public function cursorPaginate(
        int $perPage = 15,
        array | string $columns = ['*'],
        string $cursorName = 'cursor',
        ?string $cursor = null,
    ): CursorPaginator;
}
