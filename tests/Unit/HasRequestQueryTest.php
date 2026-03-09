<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Jooservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasRequestQueryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub);
    }

    #[Test]
    public function it_applies_from_request_with_filter_key(): void
    {
        $this->repo->create(['name' => 'Match', 'email' => 'm@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'Other', 'email' => 'o@x.com', 'status' => 'pending']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'status', 'value' => 'active'],
                ],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
        $this->assertSame('Match', $results->first()->name);
    }

    #[Test]
    public function it_applies_where_with_default_operator_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [['column' => 'status', 'value' => 'active']],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_from_request_with_order(): void
    {
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'order' => [
                    ['column' => 'name', 'direction' => 'asc'],
                ],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_handles_empty_request(): void
    {
        $this->repo->create(['name' => 'Only', 'email' => 'o@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', []);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function from_request_returns_same_instance_for_fluent_chaining(): void
    {
        $request = Request::create('/', 'GET', []);
        $this->assertSame($this->repo, $this->repo->fromRequest($request));
    }

    #[Test]
    public function it_applies_or_where_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'pending']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'orWhere' => [
                    ['column' => 'status', 'value' => 'active'],
                    ['column' => 'status', 'value' => 'pending'],
                ],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_applies_where_in_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'pending']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereIn' => [['column' => 'status', 'values' => ['active', 'pending']]],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_applies_where_between_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $range = [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')];
        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereBetween' => [['column' => 'created_at', 'range' => $range]],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_only_where_between_when_range_has_two_values(): void
    {
        $this->repo->create(['name' => 'X', 'email' => 'x@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereBetween' => [
                    ['column' => 'id', 'range' => [1, 10]],
                ],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_where_null_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => ['whereNull' => ['name']],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(0, $results);
    }

    #[Test]
    public function it_applies_where_not_null_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => ['whereNotNull' => ['name']],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_with_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => ['with' => ['profile']],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->relationLoaded('profile'));
    }

    #[Test]
    public function it_skips_where_between_with_insufficient_range(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereBetween' => [['column' => 'created_at', 'range' => ['only-one']]],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_order_with_default_direction_from_request(): void
    {
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'order' => [['column' => 'name']],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_applies_full_request_with_all_clause_types(): void
    {
        $this->repo->create(['name' => 'User', 'email' => 'u@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [['column' => 'status', 'value' => 'active']],
                'orWhere' => [['column' => 'status', 'value' => 'active']],
                'whereIn' => [['column' => 'status', 'values' => ['active']]],
                'whereBetween' => [
                    [
                        'column' => 'created_at',
                        'range' => [
                            now()->subDay()->toDateTimeString(),
                            now()->addDay()->toDateTimeString(),
                        ],
                    ],
                ],
                'whereNotNull' => ['name'],
                'with' => [],
                'order' => [['column' => 'name', 'direction' => 'asc']],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }
}
