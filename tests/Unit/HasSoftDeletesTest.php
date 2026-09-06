<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Tests\Stubs\SoftUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\SoftUserStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use JOOservices\LaravelRepository\Traits\HasSoftDeletes;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class HasSoftDeletesTest extends TestCase
{
    use RefreshDatabase;

    private SoftUserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new SoftUserRepositoryStub(new SoftUserStub());
    }

    #[Test]
    public function it_soft_deletes_restores_and_force_deletes(): void
    {
        $user = $this->repo->create($this->fakeSoftUserAttributes([
            'name' => 'Soft',
            'email' => 'soft@example.com',
        ]));

        $this->assertTrue($this->repo->delete($user->id));
        $this->assertNull($this->repo->find($user->id));

        $trashed = $this->repo->onlyTrashed()->get();
        $this->assertCount(1, $trashed);
        $this->assertSame($user->id, $trashed->first()->id);

        $withTrashed = $this->repo->withTrashed()->get();
        $this->assertCount(1, $withTrashed);

        $this->assertTrue($this->repo->restore($user->id));
        $this->assertNotNull($this->repo->find($user->id));
        $this->assertCount(0, $this->repo->onlyTrashed()->get());

        $this->assertTrue($this->repo->forceDelete($user->id));
        $this->assertCount(0, $this->repo->withTrashed()->get());
    }

    #[Test]
    public function it_throws_when_model_lacks_soft_deletes(): void
    {
        $repo = new class (new UserStub()) extends UserRepositoryStub {
            use HasSoftDeletes;
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must use');
        $repo->withTrashed();
    }
}
