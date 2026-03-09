<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Jooservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub);
    }

    #[Test]
    public function full_repository_crud_filter_order_work_together(): void
    {
        $user = $this->repo->create([
            'name' => 'Test', 'email' => 'test@x.com', 'status' => 'active',
        ]);
        $found = $this->repo->find($user->id);
        $this->assertSame($user->id, $found?->id);
        $this->repo->update($user->id, ['name' => 'Updated']);
        $this->assertSame('Updated', $this->repo->findOrFail($user->id)->name);
        $filtered = $this->repo->filter(['status' => 'active'])->orderBy(['name' => 'asc'])->get();
        $this->assertCount(1, $filtered);
        $this->repo->delete($user->id);
        $this->assertNull($this->repo->find($user->id));
    }

    #[Test]
    public function from_request_flows_to_paginate(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [['column' => 'status', 'value' => 'active']],
                'order' => [['column' => 'name', 'direction' => 'desc']],
            ],
        ]);
        $paginator = $this->repo->fromRequest($request)->paginate(10);
        $this->assertSame(2, $paginator->total());
        $this->assertSame('B', $paginator->items()[0]->name);
    }
}
