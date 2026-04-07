<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Jooservices\LaravelRepository\Exceptions\InvalidRequestQueryException;
use Jooservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\SearchUsersRequestFilterStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasAllowedRequestQueryTest extends TestCase
{
    use RefreshDatabase;

    private const ALLOWED_EMAIL = 'allowed@x.com';

    private const BLOCKED_EMAIL = 'blocked@x.com';

    private const FIRST_SORT_EMAIL = 'z@x.com';

    private const SECOND_SORT_EMAIL = 'a@x.com';

    private const INCLUDE_EMAIL = 'include@x.com';

    private const SCOPE_EMAIL = 'scope@test.com';

    private const FIELD_EMAIL = 'field@x.com';

    #[Test]
    public function it_applies_only_allowed_filters_in_permissive_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, ['status'], null, null, false);
        $repo->create(['name' => 'Allowed', 'email' => self::ALLOWED_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'Blocked', 'email' => self::BLOCKED_EMAIL, 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'status', 'value' => 'active'],
                    ['column' => 'name', 'value' => 'Allowed'],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_throws_for_disallowed_filters_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, ['status'], null, null, true);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'name', 'value' => 'Blocked'],
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Filter [name] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_applies_only_allowed_sorts_in_permissive_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, ['name'], null, false);
        $repo->create(['name' => 'B', 'email' => self::FIRST_SORT_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'A', 'email' => self::SECOND_SORT_EMAIL, 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'order' => [
                    ['column' => 'email', 'direction' => 'desc'],
                    ['column' => 'name', 'direction' => 'asc'],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_throws_for_disallowed_sorts_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, ['name'], null, true);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'order' => [
                    ['column' => 'email', 'direction' => 'desc'],
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Sort [email] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_applies_only_allowed_includes_in_permissive_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, null, ['profile'], false);
        $repo->create(['name' => 'A', 'email' => self::INCLUDE_EMAIL, 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'with' => ['profile', 'posts'],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertTrue($results->first()->relationLoaded('profile'));
    }

    #[Test]
    public function it_throws_for_disallowed_includes_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, null, ['profile'], true);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'with' => ['posts'],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Include [posts] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_applies_only_allowed_fields_in_permissive_mode(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub))->withAllowedFields(['name']);
        $repo->create(['name' => 'Allowed', 'email' => self::FIELD_EMAIL, 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['name', 'email'],
            ],
        ]);

        $result = $repo->fromRequest($request)->get()->first();

        $this->assertSame(['id', 'name'], array_keys($result->getAttributes()));
    }

    #[Test]
    public function it_throws_for_disallowed_fields_in_strict_mode(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, null, null, null, true))->withAllowedFields(['name']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['email'],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Field [email] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function strict_mode_can_be_enabled_from_config(): void
    {
        config()->set('laravel-repository.request_query.strict', true);

        $repo = new AllowedUserRepositoryStub(new UserStub, ['status']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'name', 'value' => 'Blocked'],
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_disallowed_scopes_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, null, null, true, ['active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'scope' => ['email_domain'],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Scope [email_domain] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_invalid_scope_parameter_counts_in_strict_mode(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, null, null, null, true, ['email_domain']))
            ->withScopeMetadata([
                'domain' => ['scope' => 'email_domain', 'parameters' => 1],
            ]);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'scope' => [
                    ['name' => 'domain', 'parameters' => []],
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Scope [domain] expects 1 parameters, 0 given.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_internal_filter_columns_when_only_aliases_are_public_in_strict_mode(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, ['email'], null, null, true))
            ->withFilterAliases(['contact' => 'email']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'email', 'value' => self::ALLOWED_EMAIL],
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Filter [email] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_disallowed_relation_count_clauses_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, null, ['profile'], true);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'has' => [
                    ['relation' => 'posts', 'operator' => '>=', 'count' => 1],
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Relation count [posts] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_internal_relations_when_only_aliases_are_public_in_strict_mode(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, null, null, ['profile'], true))
            ->withRelationAliases(['account' => 'profile']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'with' => ['profile'],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Include [profile] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_allows_aggregate_include_helpers_when_the_relation_is_allowlisted_in_strict_mode(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, null, null, ['posts'], true))
            ->withAggregateIncludes([
                'postsVotesSum' => ['relation' => 'posts', 'column' => 'votes', 'function' => 'sum'],
            ]);

        $user = $repo->create(['name' => 'Included', 'email' => self::INCLUDE_EMAIL, 'status' => 'active']);
        $user->posts()->create(['title' => 'One', 'status' => 'published', 'votes' => 3]);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'with' => ['postsVotesSum'],
            ],
        ]);

        $result = $repo->fromRequest($request)->get()->first();

        $this->assertSame(3, $result->posts_votes_sum);
    }

    #[Test]
    public function it_applies_only_allowed_scopes_in_permissive_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, null, null, false, ['active']);
        $repo->create(['name' => 'Active', 'email' => self::SCOPE_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'Other', 'email' => 'other@test.com', 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'scope' => [
                    'email_domain',
                    'active',
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_throws_for_disallowed_relation_filters_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            null,
            true,
            null,
            ['posts' => ['status']],
        );

        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereHas' => [
                    [
                        'relation' => 'profile',
                        'where' => [['column' => 'name', 'value' => 'Profile']],
                    ],
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Relation filter [profile] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_disallowed_relation_columns_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            null,
            true,
            null,
            ['posts' => ['status']],
        );

        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereHas' => [
                    [
                        'relation' => 'posts',
                        'where' => [['column' => 'title', 'value' => 'Published']],
                    ],
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Column [title] is not allowed for relation [posts].');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_disallowed_relation_columns_in_where_doesnt_have_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            null,
            true,
            null,
            ['posts' => ['status']],
        );

        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereDoesntHave' => [
                    [
                        'relation' => 'posts',
                        'where' => [['column' => 'title', 'value' => 'Published']],
                    ],
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Column [title] is not allowed for relation [posts].');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_disallowed_named_request_filters_in_strict_mode(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, null, null, null, true))
            ->withRequestFilters(['search' => SearchUsersRequestFilterStub::class]);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'filters' => [
                    'status' => 'active',
                ],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Request filter [status] is not allowed.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_unsupported_request_query_clauses_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, null, null, true);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'aggregate' => ['count'],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Request query clause [aggregate] is not supported.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_invalid_request_query_clause_shapes_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, null, null, true);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => 'status=active',
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Request query clause [where] must be an array.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_invalid_request_query_payloads_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, null, null, true);

        $request = Request::create('/', 'GET', [
            'filter' => 'not-an-array',
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Request query payload must be an array.');

        $repo->fromRequest($request);
    }

    #[Test]
    public function it_throws_for_unknown_includes_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, null, null, null, true);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'with' => ['missingRelation'],
            ],
        ]);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Relation [missingRelation] does not exist on the repository model.');

        $repo->fromRequest($request);
    }
}
