<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EloquentRepositoryTest extends TestCase
{
    #[Test]
    public function it_returns_model_from_get_model(): void
    {
        $model = new UserStub;
        $repo = new EloquentRepository($model);
        $this->assertSame($model, $repo->getModel());
    }

    #[Test]
    public function it_returns_new_query_builder(): void
    {
        $model = new UserStub;
        $repo = new EloquentRepository($model);
        $query = $repo->newQuery();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }
}
