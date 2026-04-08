<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use IteratorAggregate;
use Jooservices\LaravelRepository\Support\Order;
use Jooservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasOrderTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub);
    }

    #[Test]
    public function it_orders_by_array(): void
    {
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $results = $this->repo->orderBy(['name' => 'asc'])->get();
        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_orders_by_order_objects(): void
    {
        $this->repo->create(['name' => 'Z', 'email' => 'z@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $results = $this->repo->orderBy([new Order('name', 'asc')])->get();
        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_chains_filter_and_order(): void
    {
        $this->repo->create(['name' => 'Second', 'email' => 's@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'First', 'email' => 'f@x.com', 'status' => 'active']);
        $results = $this->repo->filter(['status' => 'active'])->orderBy(['name' => 'asc'])->get();
        $this->assertSame('First', $results->first()->name);
    }

    #[Test]
    public function it_uses_asc_when_direction_is_not_string(): void
    {
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $orders = ['name' => 1];
        $results = $this->repo->orderBy($orders)->get();
        $this->assertCount(2, $results);
        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_uses_asc_when_direction_is_not_supported(): void
    {
        $this->repo->create(['name' => 'B', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => 'z@x.com', 'status' => 'active']);
        $results = $this->repo->orderBy(['email' => 'sideways'])->get();
        $this->assertSame('B', $results->first()->name);
    }

    #[Test]
    public function it_orders_when_column_is_order_instance(): void
    {
        $this->repo->create(['name' => 'Z', 'email' => 'z@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $order = new Order('name', 'asc');
        $iterable = new class($order) implements IteratorAggregate
        {
            public function __construct(private readonly Order $order) {}

            public function getIterator(): Generator
            {
                yield $this->order => 'asc';
            }
        };
        $results = $this->repo->orderBy($iterable)->get();
        $this->assertSame('A', $results->first()->name);
    }
}
