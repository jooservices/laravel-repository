<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Jooservices\LaravelRepository\Exceptions\InvalidRequestQueryException;
use Jooservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\PostStub;
use Jooservices\LaravelRepository\Tests\Stubs\SearchUsersRequestFilterStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasRequestQueryTest extends TestCase
{
    use RefreshDatabase;

    private const ACTIVE_EMAIL = 'a@x.com';

    private const AUTHOR_EMAIL = 'author@x.com';

    private const JOHN_NAME = 'John Doe';

    private const JOHN_EMAIL = 'john@x.com';

    private const JANE_EMAIL = 'jane@x.com';

    private const NO_POSTS_NAME = 'No Posts';

    private const NO_POSTS_EMAIL = 'none@x.com';

    private const OTHER_EMAIL = 'other@x.com';

    private const SECOND_EMAIL = 'b@x.com';

    private UserRepositoryStub $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepositoryStub(new UserStub);
    }

    #[Test]
    public function it_applies_from_request_with_filter_key(): void
    {
        $this->repo->create(['name' => 'Match', 'email' => 'm@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'Other', 'email' => 'o@x.com', 'status' => 'pending']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'status', 'value' => 'active'],
                ],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
        $this->assertSame('Match', $results->first()->name);
    }

    #[Test]
    public function it_applies_where_with_default_operator_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [['column' => 'status', 'value' => 'active']],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_partial_operator_from_request(): void
    {
        $this->repo->create(['name' => self::JOHN_NAME, 'email' => self::JOHN_EMAIL, 'status' => 'active']);
        $this->repo->create(['name' => 'Jane Doe', 'email' => self::JANE_EMAIL, 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [['column' => 'name', 'operator' => 'partial', 'value' => 'john']],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame(self::JOHN_NAME, $results->first()->name);
    }

    #[Test]
    public function it_applies_supported_operator_aliases_from_request(): void
    {
        $this->repo->create(['name' => 'Low', 'email' => 'low@x.com', 'status' => 'active']);
        $this->repo->create(['name' => 'High', 'email' => 'high@x.com', 'status' => 'pending']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'id', 'operator' => 'gte', 'value' => 2],
                    ['column' => 'status', 'operator' => 'neq', 'value' => 'active'],
                ],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('High', $results->first()->name);
    }

    #[Test]
    public function it_preserves_non_strict_operator_compatibility_for_raw_operators(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'status', 'operator' => '=', 'value' => 'active'],
                ],
            ],
        ]);

        $this->assertCount(1, $this->repo->fromRequest($request)->get());
    }

    #[Test]
    public function it_applies_from_request_with_order(): void
    {
        $this->repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'order' => [
                    ['column' => 'name', 'direction' => 'asc'],
                ],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_handles_empty_request(): void
    {
        $this->repo->create(['name' => 'Only', 'email' => 'o@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', []);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function from_request_returns_same_instance_for_fluent_chaining(): void
    {
        $request = Request::create('/', 'GET', []);
        $this->assertSame($this->repo, $this->repo->fromRequest($request));
    }

    #[Test]
    public function it_applies_or_where_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'pending']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'orWhere' => [
                    ['column' => 'status', 'value' => 'active'],
                    ['column' => 'status', 'value' => 'pending'],
                ],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_applies_where_in_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'pending']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereIn' => [['column' => 'status', 'values' => ['active', 'pending']]],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_applies_where_between_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $range = [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')];
        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereBetween' => [['column' => 'created_at', 'range' => $range]],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_only_where_between_when_range_has_two_values(): void
    {
        $this->repo->create(['name' => 'X', 'email' => 'x@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereBetween' => [
                    ['column' => 'id', 'range' => [1, 10]],
                ],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_where_null_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => ['whereNull' => ['name']],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(0, $results);
    }

    #[Test]
    public function it_applies_where_not_null_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => ['whereNotNull' => ['name']],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_with_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => ['with' => ['profile']],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->relationLoaded('profile'));
    }

    #[Test]
    public function it_applies_scope_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'pending']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'scope' => ['active'],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_applies_scope_with_parameters_from_request(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $this->repo->create(['name' => 'B', 'email' => 'b@test.com', 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'scope' => [
                    ['name' => 'email_domain', 'parameters' => ['test.com']],
                ],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('B', $results->first()->name);
    }

    #[Test]
    public function it_applies_scope_aliases_from_request_metadata(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, null, null, null, true, ['email_domain']))
            ->withScopeMetadata([
                'domain' => ['scope' => 'email_domain', 'parameters' => 1],
            ]);

        $repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'B', 'email' => 'b@test.com', 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'scope' => [
                    ['name' => 'domain', 'parameters' => ['test.com']],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('B', $results->first()->name);
    }

    #[Test]
    public function it_applies_relation_count_clauses_from_request(): void
    {
        $author = $this->repo->create(['name' => 'Author', 'email' => self::AUTHOR_EMAIL, 'status' => 'active']);
        $single = $this->repo->create(['name' => 'Single', 'email' => 'single@x.com', 'status' => 'active']);
        $this->repo->create(['name' => self::NO_POSTS_NAME, 'email' => self::NO_POSTS_EMAIL, 'status' => 'active']);

        PostStub::create(['user_id' => $author->id, 'title' => 'One', 'status' => 'published']);
        PostStub::create(['user_id' => $author->id, 'title' => 'Two', 'status' => 'published']);
        PostStub::create(['user_id' => $single->id, 'title' => 'Only', 'status' => 'published']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'has' => [
                    ['relation' => 'posts', 'operator' => '>=', 'count' => 2],
                ],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Author', $results->first()->name);
    }

    #[Test]
    public function it_applies_filter_aliases_from_request_metadata(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, ['email'], null, null, true))
            ->withFilterAliases(['contact' => 'email']);

        $repo->create(['name' => 'Alias Match', 'email' => 'alias@x.com', 'status' => 'active']);
        $repo->create(['name' => 'Other', 'email' => self::OTHER_EMAIL, 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'contact', 'operator' => 'exact', 'value' => 'alias@x.com'],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Alias Match', $results->first()->name);
    }

    #[Test]
    public function it_applies_relation_aliases_and_count_exists_includes_from_request_metadata(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, null, null, ['profile', 'posts'], true))
            ->withRelationAliases(['account' => 'profile']);

        $user = $repo->create(['name' => 'Included', 'email' => 'included@x.com', 'status' => 'active']);
        PostStub::create(['user_id' => $user->id, 'title' => 'One', 'status' => 'published']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'with' => ['account', 'postsCount', 'postsExists'],
            ],
        ]);

        $result = $repo->fromRequest($request)->get()->first();

        $this->assertTrue($result->relationLoaded('profile'));
        $this->assertSame(1, $result->posts_count);
        $this->assertTrue((bool) $result->posts_exists);
    }

    #[Test]
    public function it_applies_aggregate_include_helpers_from_request_metadata(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, null, null, ['posts'], true))
            ->withAggregateIncludes([
                'postsVotesSum' => ['relation' => 'posts', 'column' => 'votes', 'function' => 'sum'],
                'postsVotesAvg' => ['relation' => 'posts', 'column' => 'votes', 'function' => 'avg'],
                'postsVotesMin' => ['relation' => 'posts', 'column' => 'votes', 'function' => 'min'],
                'postsVotesMax' => ['relation' => 'posts', 'column' => 'votes', 'function' => 'max'],
            ]);

        $user = $repo->create(['name' => 'Aggregated', 'email' => 'aggregate@x.com', 'status' => 'active']);
        PostStub::create(['user_id' => $user->id, 'title' => 'One', 'status' => 'published', 'votes' => 4]);
        PostStub::create(['user_id' => $user->id, 'title' => 'Two', 'status' => 'published', 'votes' => 8]);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'with' => ['postsVotesSum', 'postsVotesAvg', 'postsVotesMin', 'postsVotesMax'],
            ],
        ]);

        $result = $repo->fromRequest($request)->get()->first();

        $this->assertSame(12, $result->posts_votes_sum);
        $this->assertSame(6.0, (float) $result->posts_votes_avg);
        $this->assertSame(4, $result->posts_votes_min);
        $this->assertSame(8, $result->posts_votes_max);
    }

    #[Test]
    public function it_normalizes_root_filter_values_from_request_metadata(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub))
            ->withValueRules([
                'filters' => [
                    'email' => ['trim', 'lowercase'],
                ],
            ]);

        $repo->create(['name' => 'Normalized', 'email' => 'person@example.com', 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'email', 'operator' => 'exact', 'value' => '  PERSON@EXAMPLE.COM  '],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Normalized', $results->first()->name);
    }

    #[Test]
    public function it_applies_callback_style_micro_filters_and_normalizes_values(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub))
            ->withRequestFilters([
                'search' => static function (Builder $query, mixed $value): void {
                    $query->where('name', 'like', '%'.$value.'%');
                },
            ])
            ->withValueRules([
                'namedFilters' => [
                    'search' => ['trim', 'lowercase'],
                ],
            ]);

        $repo->create(['name' => self::JOHN_NAME, 'email' => self::JOHN_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'Jane Roe', 'email' => self::JANE_EMAIL, 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'filters' => [
                    'search' => '  JOHN  ',
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame(self::JOHN_NAME, $results->first()->name);
    }

    #[Test]
    public function it_normalizes_scope_parameters_from_request_metadata(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, null, null, null, false, ['email_domain']))
            ->withScopeMetadata([
                'domain' => ['scope' => 'email_domain', 'parameters' => 1],
            ])
            ->withValueRules([
                'scopes' => [
                    'domain' => ['trim', 'lowercase'],
                ],
            ]);

        $repo->create(['name' => 'Domain Match', 'email' => 'domain@example.com', 'status' => 'active']);
        $repo->create(['name' => 'Other', 'email' => 'other@test.com', 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'scope' => [
                    ['name' => 'domain', 'parameters' => ['  EXAMPLE.COM  ']],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Domain Match', $results->first()->name);
    }

    #[Test]
    public function it_normalizes_relation_filter_values_from_request_metadata(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub);
        $author = $repo->create(['name' => 'Author', 'email' => self::AUTHOR_EMAIL, 'status' => 'active']);
        $other = $repo->create(['name' => 'Other', 'email' => self::OTHER_EMAIL, 'status' => 'active']);

        PostStub::create(['user_id' => $author->id, 'title' => 'Published', 'status' => 'published', 'votes' => 1]);
        PostStub::create(['user_id' => $other->id, 'title' => 'Draft', 'status' => 'draft', 'votes' => 0]);

        $repo->withValueRules([
            'relations' => [
                'posts' => [
                    'status' => ['trim', 'lowercase'],
                ],
            ],
        ]);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereHas' => [
                    [
                        'relation' => 'posts',
                        'where' => [
                            ['column' => 'status', 'operator' => 'exact', 'value' => '  PUBLISHED  '],
                        ],
                    ],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Author', $results->first()->name);
    }

    #[Test]
    public function it_applies_where_has_from_request(): void
    {
        $author = $this->repo->create(['name' => 'Author', 'email' => self::AUTHOR_EMAIL, 'status' => 'active']);
        $other = $this->repo->create(['name' => 'Other', 'email' => self::OTHER_EMAIL, 'status' => 'active']);

        PostStub::create(['user_id' => $author->id, 'title' => 'Published', 'status' => 'published']);
        PostStub::create(['user_id' => $other->id, 'title' => 'Draft', 'status' => 'draft']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereHas' => [
                    [
                        'relation' => 'posts',
                        'where' => [
                            ['column' => 'status', 'operator' => 'exact', 'value' => 'published'],
                        ],
                    ],
                ],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Author', $results->first()->name);
    }

    #[Test]
    public function it_applies_nested_where_has_from_request(): void
    {
        $author = $this->repo->create(['name' => 'Author', 'email' => self::AUTHOR_EMAIL, 'status' => 'active']);
        $other = $this->repo->create(['name' => 'Other', 'email' => self::OTHER_EMAIL, 'status' => 'active']);

        PostStub::create(['user_id' => $author->id, 'title' => 'Published', 'status' => 'published']);
        PostStub::create(['user_id' => $other->id, 'title' => 'Draft', 'status' => 'draft']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereHas' => [
                    [
                        'relation' => 'posts.user',
                        'where' => [
                            ['column' => 'email', 'operator' => 'exact', 'value' => self::AUTHOR_EMAIL],
                        ],
                    ],
                ],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Author', $results->first()->name);
    }

    #[Test]
    public function it_applies_or_where_has_from_request(): void
    {
        $author = $this->repo->create(['name' => 'Author', 'email' => self::AUTHOR_EMAIL, 'status' => 'active']);
        $pending = $this->repo->create(['name' => 'Pending', 'email' => 'pending@x.com', 'status' => 'pending']);
        $this->repo->create(['name' => 'Other', 'email' => self::OTHER_EMAIL, 'status' => 'active']);

        PostStub::create(['user_id' => $author->id, 'title' => 'Published', 'status' => 'published']);
        PostStub::create(['user_id' => $pending->id, 'title' => 'Draft', 'status' => 'draft']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'status', 'operator' => 'exact', 'value' => 'pending'],
                ],
                'orWhereHas' => [
                    [
                        'relation' => 'posts',
                        'where' => [
                            ['column' => 'status', 'operator' => 'exact', 'value' => 'published'],
                        ],
                    ],
                ],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(2, $results);
        $this->assertSame(['Author', 'Pending'], $results->pluck('name')->sort()->values()->all());
    }

    #[Test]
    public function it_applies_where_doesnt_have_from_request(): void
    {
        $author = $this->repo->create(['name' => 'Author', 'email' => self::AUTHOR_EMAIL, 'status' => 'active']);
        $draftOnly = $this->repo->create(['name' => 'Draft Only', 'email' => 'draft@x.com', 'status' => 'active']);
        $this->repo->create(['name' => self::NO_POSTS_NAME, 'email' => self::NO_POSTS_EMAIL, 'status' => 'active']);

        PostStub::create(['user_id' => $author->id, 'title' => 'Published', 'status' => 'published']);
        PostStub::create(['user_id' => $draftOnly->id, 'title' => 'Draft', 'status' => 'draft']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereDoesntHave' => [
                    [
                        'relation' => 'posts',
                        'where' => [
                            ['column' => 'status', 'operator' => 'exact', 'value' => 'published'],
                        ],
                    ],
                ],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(2, $results);
        $this->assertSame(['Draft Only', self::NO_POSTS_NAME], $results->pluck('name')->sort()->values()->all());
    }

    #[Test]
    public function it_applies_or_where_doesnt_have_from_request(): void
    {
        $author = $this->repo->create(['name' => 'Author', 'email' => self::AUTHOR_EMAIL, 'status' => 'active']);
        $pending = $this->repo->create(['name' => 'Pending', 'email' => 'pending@x.com', 'status' => 'pending']);
        $this->repo->create(['name' => self::NO_POSTS_NAME, 'email' => self::NO_POSTS_EMAIL, 'status' => 'active']);

        PostStub::create(['user_id' => $author->id, 'title' => 'Published', 'status' => 'published']);
        PostStub::create(['user_id' => $pending->id, 'title' => 'Draft', 'status' => 'draft']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [
                    ['column' => 'status', 'operator' => 'exact', 'value' => 'pending'],
                ],
                'orWhereDoesntHave' => [
                    [
                        'relation' => 'posts',
                        'where' => [
                            ['column' => 'status', 'operator' => 'exact', 'value' => 'published'],
                        ],
                    ],
                ],
            ],
        ]);

        $results = $this->repo->fromRequest($request)->get();

        $this->assertCount(2, $results);
        $this->assertSame([self::NO_POSTS_NAME, 'Pending'], $results->pluck('name')->sort()->values()->all());
    }

    #[Test]
    public function it_applies_field_selection_from_request(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub))->withAllowedFields(['name']);
        $repo->create(['name' => 'Selected', 'email' => 'selected@x.com', 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['name'],
            ],
        ]);

        $result = $repo->fromRequest($request)->get()->first();

        $this->assertSame(['id', 'name'], array_keys($result->getAttributes()));
        $this->assertSame('Selected', $result->name);
    }

    #[Test]
    public function it_applies_named_request_filters_from_request(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub))
            ->withRequestFilters(['search' => SearchUsersRequestFilterStub::class]);

        $repo->create(['name' => self::JOHN_NAME, 'email' => self::JOHN_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'Jane Roe', 'email' => self::JANE_EMAIL, 'status' => 'active']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'filters' => [
                    'search' => 'john',
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        $this->assertCount(1, $results);
        $this->assertSame(self::JOHN_NAME, $results->first()->name);
    }

    #[Test]
    public function it_skips_where_between_with_insufficient_range(): void
    {
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'whereBetween' => [['column' => 'created_at', 'range' => ['only-one']]],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_applies_order_with_default_direction_from_request(): void
    {
        $this->repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'active']);
        $this->repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'order' => [['column' => 'name']],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertSame('A', $results->first()->name);
    }

    #[Test]
    public function it_paginates_from_request_with_default_and_max_per_page_guards(): void
    {
        config()->set('laravel-repository.default_per_page', 2);
        config()->set('laravel-repository.max_per_page', 3);

        foreach (range(1, 5) as $index) {
            $this->repo->create(['name' => 'User '.$index, 'email' => 'user'.$index.'@x.com', 'status' => 'active']);
        }

        $default = $this->repo->paginateFromRequest(Request::create('/', 'GET', []));
        $tooLarge = $this->repo->paginateFromRequest(Request::create('/', 'GET', ['per_page' => 99]));
        $invalid = $this->repo->paginateFromRequest(Request::create('/', 'GET', ['per_page' => 'many']));

        $this->assertCount(2, $default->items());
        $this->assertCount(3, $tooLarge->items());
        $this->assertCount(2, $invalid->items());
    }

    #[Test]
    public function it_throws_for_invalid_request_per_page_in_strict_mode(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, [], [], [], true);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage(
            'Request query per-page value [0] must be an integer greater than or equal to 1.',
        );

        $repo->paginateFromRequest(Request::create('/', 'GET', ['per_page' => 0]));
    }

    #[Test]
    public function paginate_from_request_resets_query_when_request_query_fails(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub, ['status'], [], [], true);
        $repo->create(['name' => 'A', 'email' => self::ACTIVE_EMAIL, 'status' => 'active']);
        $repo->create(['name' => 'B', 'email' => self::SECOND_EMAIL, 'status' => 'pending']);

        try {
            $repo->paginateFromRequest(Request::create('/', 'GET', [
                'filter' => [
                    'where' => [
                        ['column' => 'status', 'value' => 'active'],
                        ['column' => 'name', 'value' => 'A'],
                    ],
                ],
            ]));
            $this->fail('Expected strict request query validation to throw.');
        } catch (InvalidRequestQueryException) {
            $this->assertSame(2, $repo->count());
        }
    }

    #[Test]
    public function it_throws_for_too_large_request_per_page_in_strict_mode(): void
    {
        config()->set('laravel-repository.max_per_page', 2);

        $repo = new AllowedUserRepositoryStub(new UserStub, [], [], [], true);

        $this->expectException(InvalidRequestQueryException::class);
        $this->expectExceptionMessage('Request query per-page value [3] exceeds the configured maximum of [2].');

        $repo->paginateFromRequest(Request::create('/', 'GET', ['per_page' => 3]));
    }

    #[Test]
    public function it_applies_full_request_with_all_clause_types(): void
    {
        $this->repo->create(['name' => 'User', 'email' => 'u@x.com', 'status' => 'active']);
        $request = Request::create('/', 'GET', [
            'filter' => [
                'where' => [['column' => 'status', 'value' => 'active']],
                'orWhere' => [['column' => 'status', 'value' => 'active']],
                'whereIn' => [['column' => 'status', 'values' => ['active']]],
                'whereBetween' => [
                    [
                        'column' => 'created_at',
                        'range' => [
                            now()->subDay()->toDateTimeString(),
                            now()->addDay()->toDateTimeString(),
                        ],
                    ],
                ],
                'whereNotNull' => ['name'],
                'with' => [],
                'order' => [['column' => 'name', 'direction' => 'asc']],
            ],
        ]);
        $results = $this->repo->fromRequest($request)->get();
        $this->assertCount(1, $results);
    }
}
