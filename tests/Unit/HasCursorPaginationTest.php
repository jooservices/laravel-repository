<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jooservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasCursorPaginationTest extends TestCase
{
    use RefreshDatabase;

    private AllowedUserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AllowedUserRepositoryStub(new UserStub);
    }

    #[Test]
    public function it_cursor_paginates_with_existing_order(): void
    {
        $this->repo->create(['name' => 'C', 'email' => 'c@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'active']);

        $paginator = $this->repo->orderBy(['name' => 'asc'])->cursorPaginate(2);

        $this->assertCount(2, $paginator->items());
        $this->assertSame('A', $paginator->items()[0]->name);
        $this->assertSame('B', $paginator->items()[1]->name);
    }

    #[Test]
    public function it_defaults_cursor_pagination_to_primary_key_order(): void
    {
        $first = $this->repo->create(['name' => 'First', 'email' => 'first@x.com', 'status' => 'active']);
        $second = $this->repo->create(['name' => 'Second', 'email' => 'second@x.com', 'status' => 'active']);

        $paginator = $this->repo->cursorPaginate(1);

        $this->assertCount(1, $paginator->items());
        $this->assertSame($first->id, $paginator->items()[0]->id);

        $nextCursor = $paginator->nextCursor();
        $this->assertNotNull($nextCursor);

        $nextPage = $this->repo->cursorPaginate(1, ['*'], 'cursor', $nextCursor?->encode());
        $this->assertCount(1, $nextPage->items());
        $this->assertSame($second->id, $nextPage->items()[0]->id);
    }

    #[Test]
    public function it_resets_query_after_cursor_paginate(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'pending']);

        $this->repo->filter(['status' => 'active'])->cursorPaginate(1);
        $results = $this->repo->cursorPaginate(10);

        $this->assertCount(2, $results->items());
    }
}
