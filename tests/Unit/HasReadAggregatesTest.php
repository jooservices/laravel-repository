<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasReadAggregatesTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub());
    }

    #[Test]
    public function it_reads_value_and_pluck(): void
    {
        $this->repo->create($this->fakeUserAttributes([
            'name' => 'Alpha',
            'email' => 'alpha@example.com',
            'status' => 'active',
            'score' => 10,
        ]));
        $this->repo->create($this->fakeUserAttributes([
            'name' => 'Beta',
            'email' => 'beta@example.com',
            'status' => 'pending',
            'score' => 20,
        ]));

        $this->assertSame(
            'Alpha',
            $this->repo->filter(['status' => 'active'])->value('name'),
        );

        $ids = $this->repo->pluck('id');
        $this->assertCount(2, $ids);

        $namesByEmail = $this->repo->pluck('name', 'email');
        $this->assertSame('Beta', $namesByEmail['beta@example.com']);
    }

    #[Test]
    public function it_aggregates_numeric_score_column(): void
    {
        $this->repo->create($this->fakeUserAttributes(['score' => 10, 'status' => 'active']));
        $this->repo->create($this->fakeUserAttributes(['score' => 30, 'status' => 'active']));
        $this->repo->create($this->fakeUserAttributes(['score' => 5, 'status' => 'pending']));

        $this->assertSame(40, (int) $this->repo->filter(['status' => 'active'])->sum('score'));
        $this->assertSame(20.0, (float) $this->repo->filter(['status' => 'active'])->avg('score'));
        $this->assertSame(10, (int) $this->repo->filter(['status' => 'active'])->min('score'));
        $this->assertSame(30, (int) $this->repo->filter(['status' => 'active'])->max('score'));
        $this->assertSame(45, (int) $this->repo->sum('score'));
    }

    #[Test]
    public function aggregate_calls_reset_query_state(): void
    {
        $this->repo->create($this->fakeUserAttributes(['score' => 10, 'status' => 'active']));
        $this->repo->create($this->fakeUserAttributes(['score' => 20, 'status' => 'pending']));

        $this->assertSame(10, (int) $this->repo->filter(['status' => 'active'])->sum('score'));
        $this->assertSame(30, (int) $this->repo->sum('score'));
        $this->assertSame(2, $this->repo->count());
    }
}
