<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jooservices\LaravelRepository\Tests\Stubs\ActiveStatusCriteriaStub;
use Jooservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasCriteriaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_applies_pushed_criteria_to_each_fresh_query(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub);
        $repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'pending']);

        $repo->pushCriteria(new ActiveStatusCriteriaStub);

        $this->assertCount(1, $repo->get());
        $this->assertCount(1, $repo->get());
    }

    #[Test]
    public function it_can_clear_and_pop_criteria(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub);
        $repo->create(['name' => 'A', 'email' => 'a@x.com', 'status' => 'active']);
        $repo->create(['name' => 'B', 'email' => 'b@x.com', 'status' => 'pending']);

        $repo->pushCriteria(new ActiveStatusCriteriaStub);
        $this->assertInstanceOf(ActiveStatusCriteriaStub::class, $repo->popCriteria());
        $this->assertCount(2, $repo->get());

        $repo->pushCriteria(new ActiveStatusCriteriaStub);
        $repo->clearCriteria();

        $this->assertCount(2, $repo->get());
        $this->assertSame([], $repo->criteria());
    }
}
