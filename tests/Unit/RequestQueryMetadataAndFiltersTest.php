<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Jooservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\SearchUsersRequestFilterStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RequestQueryMetadataAndFiltersTest extends TestCase
{
    #[Test]
    public function it_normalizes_request_filters(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub))
            ->withRequestFilters([
                ' search ' => SearchUsersRequestFilterStub::class,
                'callback' => static function (Builder $query, mixed $value): void {
                    $query->where('name', $value);
                },
                1 => SearchUsersRequestFilterStub::class,
                'invalid' => self::class,
                '   ' => SearchUsersRequestFilterStub::class,
            ]);

        $filters = $repo->requestFilters();

        $this->assertSame(['search', 'callback'], array_keys($filters));
        $this->assertSame(SearchUsersRequestFilterStub::class, $filters['search']);
        $this->assertIsCallable($filters['callback']);
    }

    #[Test]
    public function it_normalizes_alias_and_scope_metadata(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub))
            ->withFilterAliases([
                ' contact ' => 'email',
                'skip' => '   ',
                1 => 'status',
            ])
            ->withRelationAliases([
                'account' => 'profile',
                'posts' => ' posts ',
                'bad' => '',
            ])
            ->withScopeMetadata([
                'domain' => ' email_domain ',
                'verified' => ['name' => 'active', 'parameterCount' => 0],
                'noop' => ['scope' => '   '],
                '   ' => 'ignored',
                1 => 'ignored',
            ]);

        $this->assertSame(['contact' => 'email'], $repo->filterAliases());
        $this->assertSame(['account' => 'profile', 'posts' => 'posts'], $repo->relationAliases());
        $this->assertSame([
            'domain' => ['scope' => 'email_domain', 'parameters' => null],
            'verified' => ['scope' => 'active', 'parameters' => 0],
        ], $repo->scopeMetadata());
    }

    #[Test]
    public function it_normalizes_aggregate_includes_and_value_rules(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub))
            ->withAggregateIncludes([
                'postsVotesSum' => [
                    'relation' => 'posts',
                    'field' => 'votes',
                    'aggregate' => 'SUM',
                ],
                'postsVotesAvg' => [
                    'relation' => 'posts',
                    'column' => 'votes',
                    'function' => 'avg',
                    'alias' => 'votes_average',
                ],
                '   ' => [
                    'relation' => 'posts',
                    'column' => 'votes',
                    'function' => 'sum',
                ],
                'invalidFunction' => [
                    'relation' => 'posts',
                    'column' => 'votes',
                    'function' => 'median',
                ],
                1 => [
                    'relation' => 'posts',
                    'column' => 'votes',
                    'function' => 'sum',
                ],
                'invalidShape' => 'skip',
            ])
            ->withValueRules([
                'filters' => [
                    'email' => ' trim ',
                    '   ' => ['trim'],
                    1 => ['trim'],
                    'skip' => 123,
                ],
                'namedFilters' => [
                    'search' => ['trim', ['csv', ','], '', 123],
                ],
                'scopes' => [
                    'domain' => [['rule' => 'lowercase']],
                ],
                'relations' => [
                    'posts' => [
                        'status' => ['trim'],
                        'votes' => 123,
                    ],
                    'comments' => [
                        'count' => 123,
                    ],
                    'bad' => 'skip',
                    '   ' => ['status' => ['trim']],
                ],
            ]);

        $this->assertSame([
            'postsVotesSum' => [
                'relation' => 'posts',
                'column' => 'votes',
                'function' => 'sum',
                'attribute' => 'posts_votes_sum',
            ],
            'postsVotesAvg' => [
                'relation' => 'posts',
                'column' => 'votes',
                'function' => 'avg',
                'attribute' => 'votes_average',
            ],
        ], $repo->aggregateIncludes());

        $this->assertSame([
            'filters' => ['email' => ['trim']],
            'namedFilters' => ['search' => ['trim', ['csv', ',']]],
            'scopes' => ['domain' => [['rule' => 'lowercase']]],
            'relations' => ['posts' => ['status' => ['trim']]],
        ], $repo->valueRules());

        $emptyRulesRepo = (new AllowedUserRepositoryStub(new UserStub))->withValueRules([
            'filters' => 'skip',
            'namedFilters' => 'skip',
            'scopes' => 'skip',
            'relations' => 'skip',
        ]);

        $this->assertSame([
            'filters' => [],
            'namedFilters' => [],
            'scopes' => [],
            'relations' => [],
        ], $emptyRulesRepo->valueRules());
    }
}
