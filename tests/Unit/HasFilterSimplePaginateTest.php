<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasFilterSimplePaginateTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub());
    }

    #[Test]
    public function it_simple_paginates_filtered_results(): void
    {
        $this->repo->create($this->fakeUserAttributes(['name' => 'A', 'status' => 'active']));
        $this->repo->create($this->fakeUserAttributes(['name' => 'B', 'status' => 'active']));
        $this->repo->create($this->fakeUserAttributes(['name' => 'C', 'status' => 'pending']));

        $paginator = $this->repo->filter(['status' => 'active'])->simplePaginate(1);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertCount(1, $paginator->items());
        $this->assertTrue($paginator->hasMorePages());
    }

    #[Test]
    public function it_resets_query_after_simple_paginate(): void
    {
        $this->repo->create($this->fakeUserAttributes(['status' => 'active']));
        $this->repo->create($this->fakeUserAttributes(['status' => 'pending']));

        $this->repo->filter(['status' => 'active'])->simplePaginate(10);

        $this->assertCount(2, $this->repo->get());
    }
}
