<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasCrudExtendedTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub());
    }

    #[Test]
    public function it_finds_many_by_ids(): void
    {
        $first = $this->repo->create($this->fakeUserAttributes(['name' => 'First']));
        $second = $this->repo->create($this->fakeUserAttributes(['name' => 'Second']));
        $this->repo->create($this->fakeUserAttributes(['name' => 'Third']));

        $found = $this->repo->findMany([$first->id, $second->id]);

        $this->assertCount(2, $found);
        $this->assertTrue($found->contains(fn(UserStub $user): bool => $user->id === $first->id));
        $this->assertTrue($found->contains(fn(UserStub $user): bool => $user->id === $second->id));
    }

    #[Test]
    public function it_finds_by_attributes(): void
    {
        $created = $this->repo->create($this->fakeUserAttributes([
            'name' => 'Lookup',
            'email' => 'lookup@example.com',
        ]));

        $found = $this->repo->findBy(['email' => 'lookup@example.com']);

        $this->assertNotNull($found);
        $this->assertSame($created->id, $found->id);
        $this->assertNull($this->repo->findBy(['email' => 'missing@example.com']));
    }

    #[Test]
    public function it_finds_by_attributes_or_fails(): void
    {
        $created = $this->repo->create($this->fakeUserAttributes([
            'email' => 'orfail@example.com',
        ]));

        $found = $this->repo->findByOrFail(['email' => 'orfail@example.com']);

        $this->assertSame($created->id, $found->id);

        $this->expectException(ModelNotFoundException::class);
        $this->repo->findByOrFail(['email' => 'gone@example.com']);
    }

    #[Test]
    public function it_updates_or_creates(): void
    {
        $created = $this->repo->updateOrCreate(
            ['email' => 'upsert-user@example.com'],
            $this->fakeUserAttributes([
                'name' => 'Created',
                'email' => 'upsert-user@example.com',
                'status' => 'active',
                'score' => 10,
            ]),
        );

        $this->assertSame('Created', $created->name);

        $updated = $this->repo->updateOrCreate(
            ['email' => 'upsert-user@example.com'],
            ['name' => 'Updated', 'score' => 20],
        );

        $this->assertSame($created->id, $updated->id);
        $this->assertSame('Updated', $updated->name);
        $this->assertSame(20, $updated->score);
        $this->assertSame(1, $this->repo->count());
    }

    #[Test]
    public function it_upserts_rows_by_primary_key(): void
    {
        $existing = $this->repo->create($this->fakeUserAttributes([
            'name' => 'Before',
            'email' => 'before@example.com',
            'score' => 1,
        ]));

        $affected = $this->repo->upsert(
            [
                [
                    'id' => $existing->id,
                    'name' => 'After',
                    'email' => 'before@example.com',
                    'status' => 'active',
                    'score' => 50,
                ],
                [
                    'id' => $existing->id + 1,
                    'name' => 'Inserted',
                    'email' => 'inserted@example.com',
                    'status' => 'pending',
                    'score' => 5,
                ],
            ],
            ['id'],
            ['name', 'score', 'status'],
        );

        $this->assertGreaterThan(0, $affected);
        $this->assertSame('After', $this->repo->findOrFail($existing->id)->name);
        $this->assertSame(50, $this->repo->findOrFail($existing->id)->score);
        $this->assertNotNull($this->repo->findBy(['email' => 'inserted@example.com']));
    }
}
