<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jooservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasReadTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub);
    }

    #[Test]
    public function it_returns_the_first_matching_record_and_resets_the_query(): void
    {
        $this->repo->create(['name' => 'First', 'email' => 'first@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'Second', 'email' => 'second@x.com', 'status' => 'pending']);

        $first = $this->repo->filter(['status' => 'active'])->first();

        $this->assertSame('First', $first?->name);
        $this->assertSame(2, $this->repo->count());
    }

    #[Test]
    public function it_can_return_the_first_record_or_fail(): void
    {
        $this->repo->create(['name' => 'Only', 'email' => 'only@x.com', 'status' => 'active']);

        $first = $this->repo->filter(['status' => 'active'])->firstOrFail();

        $this->assertSame('Only', $first->name);
    }

    #[Test]
    public function it_throws_when_first_or_fail_cannot_find_a_record(): void
    {
        $this->repo->create(['name' => 'Visible', 'email' => 'visible@x.com', 'status' => 'active']);

        try {
            $this->repo->filter(['status' => 'missing'])->firstOrFail();
            $this->fail('Expected firstOrFail to throw.');
        } catch (ModelNotFoundException) {
            $this->assertSame(1, $this->repo->count());
        }
    }

    #[Test]
    public function it_can_check_existence_and_count_results(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'C', 'email' => 'c@x.com', 'status' => 'pending']);

        $this->assertTrue($this->repo->filter(['status' => 'active'])->exists());
        $this->assertSame(2, $this->repo->filter(['status' => 'active'])->count());
        $this->assertFalse($this->repo->filter(['status' => 'archived'])->exists());
    }

    #[Test]
    public function exists_and_count_do_not_leak_filters_to_later_calls(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a2@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b2@x.com', 'status' => 'pending']);

        $this->assertTrue($this->repo->filter(['status' => 'active'])->exists());
        $this->assertSame(2, $this->repo->count());

        $this->assertSame(1, $this->repo->filter(['status' => 'pending'])->count());
        $this->assertSame(2, $this->repo->count());
    }
}
