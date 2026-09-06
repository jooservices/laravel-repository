<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasCacheTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_cache_and_forget_repository_results(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());
        $repo->create($this->fakeUserAttributes(['name' => 'A', 'status' => 'active']));

        $cachedCount = $repo->remember(
            'users.active.count',
            300,
            static function (AllowedUserRepositoryStub $repository): int {
                return $repository->filter(['status' => 'active'])->count();
            },
        );

        $repo->create($this->fakeUserAttributes(['name' => 'B', 'status' => 'active']));

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

        $repo = (new AllowedUserRepositoryStub(new UserStub()))->useCacheStore('repository_test');
        $key = $repo->cacheKey('users.count', ['status' => 'active', 1]);

        $this->assertStringStartsWith(
            'JOOservices.LaravelRepository.Tests.Stubs.AllowedUserRepositoryStub.users.count.',
            $key,
        );
        $this->assertMatchesRegularExpression('/\.[a-f0-9]{32}$/', $key);
        $this->assertNotSame(
            $repo->cacheKey('users.count', [['a.b', 'c']]),
            $repo->cacheKey('users.count', [['a', 'b.c']]),
        );

        $cached = $repo->rememberForever($key, static fn(): int => 10);
        $again = $repo->rememberForever($key, static fn(): int => 20);

        $this->assertSame(10, $cached);
        $this->assertSame(10, $again);
        $this->assertTrue($repo->forgetCache($key));
        $this->assertSame(30, $repo->rememberForever($key, static fn(): int => 30));
    }
}
