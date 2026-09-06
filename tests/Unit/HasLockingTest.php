<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use JOOservices\LaravelRepository\Traits\HasDebug;
use JOOservices\LaravelRepository\Traits\HasLocking;
use PHPUnit\Framework\Attributes\Test;

class HasLockingTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new class (new UserStub()) extends UserRepositoryStub {
            use HasDebug;
            use HasLocking;
        };
    }

    #[Test]
    public function lock_for_update_chains_and_returns_self(): void
    {
        $this->repo->create($this->fakeUserAttributes(['status' => 'active']));

        $result = $this->repo->filter(['status' => 'active'])->lockForUpdate();

        $this->assertSame($this->repo, $result);
        $this->assertCount(1, $result->get());
    }

    #[Test]
    public function shared_lock_chains_and_returns_self(): void
    {
        $this->repo->create($this->fakeUserAttributes(['status' => 'pending']));

        $result = $this->repo->filter(['status' => 'pending'])->sharedLock();

        $this->assertSame($this->repo, $result);

        $sql = strtolower($this->repo->filter(['status' => 'pending'])->sharedLock()->toSql());
        $this->assertStringContainsString('select', $sql);
    }

    #[Test]
    public function lock_methods_are_chainable_before_get(): void
    {
        $this->repo->create($this->fakeUserAttributes(['name' => 'Locked', 'status' => 'active']));

        $results = $this->repo
            ->filter(['status' => 'active'])
            ->lockForUpdate()
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('Locked', $results->first()->name);
    }
}
