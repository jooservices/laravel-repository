<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use JOOservices\LaravelRepository\Traits\HasDebug;
use PHPUnit\Framework\Attributes\Test;

class HasDebugTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new class (new UserStub()) extends UserRepositoryStub {
            use HasDebug;
        };
    }

    #[Test]
    public function it_exposes_sql_and_bindings_without_dumping(): void
    {
        $this->repo->create($this->fakeUserAttributes(['status' => 'active']));

        $payload = $this->repo->filter(['status' => 'active'])->toQuery();
        $this->assertStringContainsString('select', strtolower($payload['sql']));
        $this->assertStringContainsString('status', $payload['sql']);
        $this->assertSame(['active'], $payload['bindings']);

        $this->repo->get();

        $sql = $this->repo->filter(['status' => 'pending'])->toSql();
        $this->assertStringContainsString('select', strtolower($sql));

        $this->repo->get();

        $bindings = $this->repo->filter(['status' => 'pending'])->getQueryBindings();
        $this->assertSame(['pending'], $bindings);
    }
}
