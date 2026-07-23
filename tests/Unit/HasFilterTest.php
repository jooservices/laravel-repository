<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jooservices\LaravelRepository\Support\Filter;
use Jooservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasFilterTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub);
    }

    #[Test]
    public function it_filters_by_array_and_gets(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'pending']);
        $results = $this->repo->filter(['status' => 'active'])->get();
        $this->assertCount(1, $results);
        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_filters_by_filter_objects(): void
    {
        $this->repo->create(['name' => 'John', 'email' => 'john@x.com', 'status' => 'active']);
        $results = $this->repo->filter([new Filter('name', 'John')])->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_paginates(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $paginator = $this->repo->filter(['status' => 'active'])->paginate(10);
        $this->assertSame(1, $paginator->total());
        $this->assertCount(1, $paginator->items());
    }

    #[Test]
    public function it_resets_query_after_get(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->filter(['status' => 'active'])->get();
        $all = $this->repo->get();
        $this->assertCount(1, $all);
        $this->assertSame('A', $all->first()->name);
    }

    #[Test]
    public function it_resets_query_after_paginate(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'pending']);

        $this->repo->filter(['status' => 'active'])->paginate(1);

        $this->assertCount(2, $this->repo->get());
    }

    #[Test]
    public function successive_filtered_queries_do_not_leak_prior_filters(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a-cross@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b-cross@x.com', 'status' => 'pending']);

        $active = $this->repo->filter(['status' => 'active'])->get();
        $pending = $this->repo->filter(['status' => 'pending'])->get();
        $all = $this->repo->get();

        $this->assertCount(1, $active);
        $this->assertSame('A', $active->first()->name);
        $this->assertCount(1, $pending);
        $this->assertSame('B', $pending->first()->name);
        $this->assertCount(2, $all);
    }
}
