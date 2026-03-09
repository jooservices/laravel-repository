<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jooservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasCrudTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub);
    }

    #[Test]
    public function it_creates_a_model(): void
    {
        $user = $this->repo->create([
            'name' => 'John', 'email' => 'john@example.com', 'status' => 'active',
        ]);
        $this->assertInstanceOf(UserStub::class, $user);
        $this->assertSame('John', $user->name);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    #[Test]
    public function it_finds_by_id(): void
    {
        $created = $this->repo->create([
            'name' => 'Jane', 'email' => 'jane@example.com', 'status' => 'active',
        ]);
        $found = $this->repo->find($created->id);
        $this->assertNotNull($found);
        $this->assertSame($created->id, $found->id);
    }

    #[Test]
    public function it_returns_null_for_missing_id(): void
    {
        $this->assertNull($this->repo->find(99999));
    }

    #[Test]
    public function it_finds_or_fails(): void
    {
        $created = $this->repo->create([
            'name' => 'Bob', 'email' => 'bob@example.com', 'status' => 'active',
        ]);
        $found = $this->repo->findOrFail($created->id);
        $this->assertSame($created->id, $found->id);
    }

    #[Test]
    public function it_throws_on_find_or_fail_missing(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repo->findOrFail(99999);
    }

    #[Test]
    public function it_returns_all(): void
    {
        $this->repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'active']);
        $all = $this->repo->all();
        $this->assertCount(2, $all);
    }

    #[Test]
    public function it_updates(): void
    {
        $user = $this->repo->create([
            'name' => 'Old', 'email' => 'old@x.com', 'status' => 'active',
        ]);
        $result = $this->repo->update($user->id, ['name' => 'New']);
        $this->assertTrue($result);
        $user->refresh();
        $this->assertSame('New', $user->name);
    }

    #[Test]
    public function it_deletes(): void
    {
        $user = $this->repo->create([
            'name' => 'Del', 'email' => 'del@x.com', 'status' => 'active',
        ]);
        $result = $this->repo->delete($user->id);
        $this->assertTrue($result);
        $this->assertNull($this->repo->find($user->id));
    }
}
