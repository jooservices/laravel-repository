<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Tests\Stubs\ApiUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ApiRepositoryPresetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function api_repository_supports_filter_and_paginate(): void
    {
        $repo = new ApiUserRepositoryStub(new UserStub());
        $repo->create($this->fakeUserAttributes(['name' => 'Match', 'status' => 'active']));
        $repo->create($this->fakeUserAttributes(['name' => 'Other', 'status' => 'pending']));

        $paginator = $repo->filter(['status' => 'active'])->paginate(10);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('Match', $paginator->items()[0]->name);
    }
}
