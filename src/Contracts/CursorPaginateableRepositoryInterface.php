<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;

interface CursorPaginateableRepositoryInterface
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
    ): CursorPaginator;
}
