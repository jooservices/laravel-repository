<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jooservices\LaravelRepository\Contracts\CriteriaInterface;
use Jooservices\LaravelRepository\Tests\Stubs\ActiveStatusCriteriaStub;
use Jooservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

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

    #[Test]
    public function it_exposes_criteria_and_does_not_reapply_the_same_query(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub);
        $applications = 0;

        $criteria = new class($applications) implements CriteriaInterface
        {
            public function __construct(private int &$applications) {}

            public function apply(\Illuminate\Database\Eloquent\Builder $query): void
            {
                $this->applications++;
            }
        };

        $repo->filter(['status' => 'active']);
        $query = $this->invokeProtected($repo, 'getQuery');

        $repo->pushCriteria($criteria);
        $this->assertCount(1, $repo->criteria());

        $repo->applyCriteria($query);
        $repo->applyCriteria($query);

        $this->assertSame(1, $applications);

        $repo->clearCriteria();
        $repo->applyCriteria(UserStub::query());

        $this->assertSame(1, $applications);
    }

    private function invokeProtected(object $object, string $method): mixed
    {
        $reflection = new ReflectionMethod($object, $method);

        return $reflection->invoke($object);
    }
}
