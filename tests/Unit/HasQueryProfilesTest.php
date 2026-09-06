<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use InvalidArgumentException;
use JOOservices\LaravelRepository\Tests\Stubs\ProfileUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasQueryProfilesTest extends TestCase
{
    #[Test]
    public function for_profile_applies_allowlists(): void
    {
        $repo = (new ProfileUserRepositoryStub(new UserStub()))
            ->withQueryProfiles([
                'admin' => [
                    'filters' => ['status', 'email'],
                    'sorts' => ['name', 'id'],
                    'includes' => ['posts'],
                    'strict' => true,
                ],
                'public' => [
                    'filters' => ['status'],
                    'sorts' => ['name'],
                    'strict' => false,
                ],
            ]);

        $repo->forProfile('admin');

        $this->assertSame(['status', 'email'], $repo->allowedFilters());
        $this->assertSame(['name', 'id'], $repo->allowedSorts());
        $this->assertSame(['posts'], $repo->allowedIncludes());
        $this->assertTrue($repo->isRequestQueryStrict());

        $repo->forProfile('public');

        $this->assertSame(['status'], $repo->allowedFilters());
        $this->assertSame(['name'], $repo->allowedSorts());
        $this->assertNull($repo->allowedIncludes());
        $this->assertFalse($repo->isRequestQueryStrict());
    }

    #[Test]
    public function for_profile_keeps_baseline_allowlists_when_omitted(): void
    {
        $repo = (new ProfileUserRepositoryStub(new UserStub()))
            ->withAllowedIncludes([])
            ->withRequestQueryStrict(true)
            ->withQueryProfiles([
                'public' => [
                    'filters' => ['status'],
                ],
            ]);

        $repo->forProfile('public');

        $this->assertSame(['status'], $repo->allowedFilters());
        $this->assertSame([], $repo->allowedIncludes());
        $this->assertTrue($repo->isRequestQueryStrict());
    }

    #[Test]
    public function unknown_profile_throws(): void
    {
        $repo = (new ProfileUserRepositoryStub(new UserStub()))
            ->withQueryProfiles([
                'admin' => ['filters' => ['status']],
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown query profile [missing]');
        $repo->forProfile('missing');
    }
}
