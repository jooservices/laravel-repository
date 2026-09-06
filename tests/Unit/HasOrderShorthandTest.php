<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasOrderShorthandTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub());
    }

    #[Test]
    public function it_orders_with_descending_shorthand(): void
    {
        $this->repo->create($this->fakeUserAttributes(['name' => 'Alpha']));
        $this->repo->create($this->fakeUserAttributes(['name' => 'Zulu']));

        $results = $this->repo->orderBy(['-name'])->get();

        $this->assertSame('Zulu', $results->first()->name);
        $this->assertSame('Alpha', $results->last()->name);
    }

    #[Test]
    public function it_orders_with_comma_separated_shorthand(): void
    {
        $this->repo->create($this->fakeUserAttributes(['name' => 'Same', 'email' => 'b@example.com']));
        $this->repo->create($this->fakeUserAttributes(['name' => 'Same', 'email' => 'a@example.com']));
        $this->repo->create($this->fakeUserAttributes(['name' => 'Other', 'email' => 'z@example.com']));

        $results = $this->repo->orderBy(['name,-id'])->get();

        $this->assertSame('Other', $results->first()->name);
        $this->assertSame('Same', $results->get(1)->name);
        $this->assertGreaterThan($results->get(2)->id, $results->get(1)->id);
    }

    #[Test]
    public function it_clears_orders(): void
    {
        $first = $this->repo->create($this->fakeUserAttributes(['name' => 'Zulu']));
        $second = $this->repo->create($this->fakeUserAttributes(['name' => 'Alpha']));

        $ordered = $this->repo->orderBy(['-name'])->get();
        $this->assertSame('Zulu', $ordered->first()->name);

        $cleared = $this->repo->orderBy(['-name'])->clearOrders()->get();
        $this->assertSame($first->id, $cleared->first()->id);
        $this->assertSame($second->id, $cleared->last()->id);
    }
}
