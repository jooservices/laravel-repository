<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Jooservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasIterationTest extends TestCase
{
    use RefreshDatabase;

    private const FIRST_EMAIL = 'a@x.com';

    private const SECOND_EMAIL = 'b@x.com';

    #[Test]
    public function it_chunks_filtered_results_and_resets_the_query(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub);
        $repo->create(['name' => 'A', 'email' => self::FIRST_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'pending']);

        $names = [];
        $result = $repo->filter(['status' => 'active'])->chunk(1, function (Collection $users) use (&$names): void {
            foreach ($users as $user) {
                $names[] = $user->name;
            }
        });

        $this->assertTrue($result);
        $this->assertSame(['A'], $names);
        $this->assertCount(2, $repo->get());
    }

    #[Test]
    public function it_returns_a_lazy_collection_and_resets_the_query(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub);
        $repo->create(['name' => 'A', 'email' => self::FIRST_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'pending']);

        $names = $repo->filter(['status' => 'active'])->lazy(1)->map(
            static fn (UserStub $user): string => $user->name,
        )->values()->all();

        $this->assertSame(['A'], $names);
        $this->assertCount(2, $repo->get());
    }

    #[Test]
    public function it_returns_a_cursor_and_resets_the_query(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub);
        $repo->create(['name' => 'A', 'email' => self::FIRST_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'pending']);

        $names = $repo->filter(['status' => 'active'])->cursor()->map(
            static fn (UserStub $user): string => $user->name,
        )->values()->all();

        $this->assertSame(['A'], $names);
        $this->assertCount(2, $repo->get());
    }

    #[Test]
    public function it_returns_lazy_by_id_results_and_resets_the_query(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub);
        $repo->create(['name' => 'A', 'email' => self::FIRST_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'active']);

        $names = $repo->filter(['status' => 'active'])->lazyById(1)->map(
            static fn (UserStub $user): string => $user->name,
        )->values()->all();

        $this->assertSame(['A', 'B'], $names);
        $this->assertCount(2, $repo->get());
    }
}
