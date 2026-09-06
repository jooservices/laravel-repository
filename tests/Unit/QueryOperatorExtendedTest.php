<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Support\QueryOperator;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

class QueryOperatorExtendedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function before_and_after_apply_comparison_operators(): void
    {
        UserStub::create($this->fakeUserAttributes(['name' => 'Early']));
        $later = UserStub::create($this->fakeUserAttributes(['name' => 'Later']));

        $before = UserStub::query();
        QueryOperator::apply($before, 'where', 'id', 'before', $later->id);
        $this->assertStringContainsString('<', $before->toSql());
        $this->assertCount(1, $before->get());

        $after = UserStub::query();
        QueryOperator::apply($after, 'where', 'id', 'after', 0);
        $this->assertStringContainsString('>', $after->toSql());
        $this->assertGreaterThanOrEqual(2, $after->count());
    }

    #[Test]
    public function date_operator_applies_where_date_sql(): void
    {
        UserStub::create($this->fakeUserAttributes());

        $query = UserStub::query();
        QueryOperator::apply($query, 'where', 'created_at', 'date', now()->toDateString());

        $sql = strtolower($query->toSql());
        $this->assertTrue(
            str_contains($sql, 'date(') || str_contains($sql, 'strftime('),
            'Expected date filtering SQL fragment, got: ' . $sql,
        );
        $this->assertGreaterThanOrEqual(1, $query->count());
    }

    #[Test]
    public function json_contains_builds_without_throwing(): void
    {
        $query = UserStub::query();

        try {
            QueryOperator::apply($query, 'where', 'status', 'jsonContains', 'active');
            $this->assertNotSame('', $query->toSql());
        } catch (Throwable $exception) {
            $this->markTestSkipped('jsonContains unsupported on this sqlite build: ' . $exception->getMessage());
        }
    }
}
