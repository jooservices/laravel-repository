<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JOOservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CursorPaginateFromRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function cursor_paginate_from_request_returns_cursor_paginator(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());
        $repo->create($this->fakeUserAttributes(['name' => 'A', 'status' => 'active']));
        $repo->create($this->fakeUserAttributes(['name' => 'B', 'status' => 'active']));
        $repo->create($this->fakeUserAttributes(['name' => 'C', 'status' => 'pending']));

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [['column' => 'status', 'value' => 'active']],
                'order' => [['column' => 'name', 'direction' => 'asc']],
            ],
            'per_page' => 1,
        ]);

        $paginator = $repo->cursorPaginateFromRequest($request);

        $this->assertInstanceOf(CursorPaginator::class, $paginator);
        $this->assertCount(1, $paginator->items());
        $this->assertSame('A', $paginator->items()[0]->name);
        $this->assertTrue($paginator->hasMorePages());
    }
}
