<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface RequestQueryRepositoryInterface
{
    public function fromRequest(Request $request): static;

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginateFromRequest(Request $request, string $perPageKey = 'per_page'): LengthAwarePaginator;

    /**
     * @return CursorPaginator<int, Model>
     */
    public function cursorPaginateFromRequest(
        Request $request,
        string $perPageKey = 'per_page',
        string $cursorName = 'cursor',
    ): CursorPaginator;
}
