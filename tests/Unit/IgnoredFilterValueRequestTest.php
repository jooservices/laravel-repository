<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JOOservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IgnoredFilterValueRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ignore_value_rule_skips_where_clause(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub()))
            ->withValueRules([
                'filters' => [
                    'status' => ['ignore'],
                ],
            ]);

        $repo->create($this->fakeUserAttributes(['name' => 'Active', 'status' => 'active']));
        $repo->create($this->fakeUserAttributes(['name' => 'Pending', 'status' => 'pending']));

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'status', 'value' => '*'],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function non_ignored_status_still_filters(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub()))
            ->withValueRules([
                'filters' => [
                    'status' => ['ignore'],
                ],
            ]);

        $repo->create($this->fakeUserAttributes(['name' => 'Active', 'status' => 'active']));
        $repo->create($this->fakeUserAttributes(['name' => 'Pending', 'status' => 'pending']));

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'status', 'value' => 'active'],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Active', $results->first()->name);
    }
}
