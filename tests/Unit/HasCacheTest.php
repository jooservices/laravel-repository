<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jooservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasCacheTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_cache_and_forget_repository_results(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub);
        $repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);

        $cachedCount = $repo->remember(
            'users.active.count',
            300,
            static function (AllowedUserRepositoryStub $repository): int {
                return $repository->filter(['status' => 'active'])->count();
            },
        );

        $repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'active']);

        $stillCachedCount = $repo->remember(
            'users.active.count',
            300,
            static function (AllowedUserRepositoryStub $repository): int {
                return $repository->filter(['status' => 'active'])->count();
            },
        );

        $repo->forgetCache('users.active.count');

        $refreshedCount = $repo->rememberForever(
            'users.active.count',
            static function (AllowedUserRepositoryStub $repository): int {
                return $repository->filter(['status' => 'active'])->count();
            },
        );

        $this->assertSame(1, $cachedCount);
        $this->assertSame(1, $stillCachedCount);
        $this->assertSame(2, $refreshedCount);
    }

    #[Test]
    public function it_can_use_custom_cache_store_and_compose_cache_keys(): void
    {
        config()->set('cache.stores.repository_test', [
            'driver' => 'array',
            'serialize' => false,
        ]);

        $repo = (new AllowedUserRepositoryStub(new UserStub))->useCacheStore('repository_test');
        $key = $repo->cacheKey('users.count', ['status' => 'active', 1]);

        $this->assertSame(
            'Jooservices.LaravelRepository.Tests.Stubs.AllowedUserRepositoryStub.users.count.status:active.1',
            $key,
        );

        $cached = $repo->rememberForever($key, static fn (): int => 10);
        $again = $repo->rememberForever($key, static fn (): int => 20);

        $this->assertSame(10, $cached);
        $this->assertSame(10, $again);
        $this->assertTrue($repo->forgetCache($key));
        $this->assertSame(30, $repo->rememberForever($key, static fn (): int => 30));
    }
}
