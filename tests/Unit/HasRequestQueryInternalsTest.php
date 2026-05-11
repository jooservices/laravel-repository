<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Http\Request;
use Jooservices\LaravelRepository\Contracts\AllowsRequestQueryInterface;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use Jooservices\LaravelRepository\Traits\HasAllowedRequestQuery;
use Jooservices\LaravelRepository\Traits\HasRequestQuery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use stdClass;
use Throwable;

class HasRequestQueryInternalsTest extends TestCase
{
    private const RAW_FILTER_VALUE = ' value ';

    private const RAW_SCOPE_VALUE = ' Value ';

    #[Test]
    public function it_returns_default_helper_state_without_optional_interfaces(): void
    {
        $repo = new UserRepositoryStub(new UserStub);

        $this->assertNull($this->invokePrivate($repo, 'requestQueryAllowedFilters'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryAllowedSorts'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryAllowedIncludes'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryAllowedFields'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryAllowedScopes'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryAllowedRelationFilters'));
        $this->assertSame([], $this->invokePrivate($repo, 'requestQueryFilterAliases'));
        $this->assertSame([], $this->invokePrivate($repo, 'requestQueryRelationAliases'));
        $this->assertSame([], $this->invokePrivate($repo, 'requestQueryScopeMetadata'));
        $this->assertSame([], $this->invokePrivate($repo, 'requestQueryAggregateIncludes'));
        $this->assertSame([
            'filters' => [],
            'namedFilters' => [],
            'scopes' => [],
            'relations' => [],
        ], $this->invokePrivate($repo, 'requestQueryValueRules'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryVisibleFilters'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryVisibleScopes'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryVisibleIncludes'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryVisibleRelationFilters'));
        $this->assertNull($this->invokePrivate($repo, 'requestQueryVisibleRelationCounts'));
        $this->assertFalse($this->invokePrivate($repo, 'requestQueryStrictMode'));
        $this->assertSame('email', $this->invokePrivate($repo, 'resolveFilterAlias', ['email']));
        $this->assertSame('posts', $this->invokePrivate($repo, 'resolveRelationAlias', ['posts']));
        $this->assertSame(
            self::RAW_FILTER_VALUE,
            $this->invokePrivate($repo, 'normalizeFilterValue', ['email', self::RAW_FILTER_VALUE]),
        );
        $this->assertSame(
            self::RAW_FILTER_VALUE,
            $this->invokePrivate($repo, 'normalizeNamedFilterValue', ['search', self::RAW_FILTER_VALUE]),
        );
        $this->assertSame(
            [self::RAW_SCOPE_VALUE],
            $this->invokePrivate($repo, 'normalizeScopeParameters', ['domain', [self::RAW_SCOPE_VALUE]]),
        );
        $this->assertSame(
            self::RAW_SCOPE_VALUE,
            $this->invokePrivate(
                $repo,
                'normalizeRelationFilterValue',
                ['posts', 'status', self::RAW_SCOPE_VALUE],
            ),
        );
    }

    #[Test]
    public function it_exposes_private_helper_resolution_for_allowed_request_query_repositories(): void
    {
        $repo = (new AllowedUserRepositoryStub(
            new UserStub,
            ['email'],
            ['name'],
            ['profile', 'posts'],
            true,
            ['email_domain'],
            ['posts' => ['status']],
        ))
            ->withAllowedFields(['name'])
            ->withFilterAliases(['contact' => 'email'])
            ->withRelationAliases(['account' => 'profile', 'articles' => 'posts'])
            ->withScopeMetadata([
                'domain' => ['scope' => 'email_domain', 'parameters' => 1],
            ])
            ->withAggregateIncludes([
                'postsVotesSum' => ['relation' => 'posts', 'column' => 'votes', 'function' => 'sum'],
            ])
            ->withValueRules([
                'filters' => ['email' => ['trim', 'lowercase']],
                'namedFilters' => ['search' => ['trim', 'lowercase']],
                'scopes' => ['domain' => ['trim', 'lowercase']],
                'relations' => ['posts' => ['status' => ['trim', 'lowercase']]],
            ]);

        $this->assertSame(['email'], $this->invokePrivate($repo, 'requestQueryAllowedFilters'));
        $this->assertSame(['name'], $this->invokePrivate($repo, 'requestQueryAllowedSorts'));
        $this->assertSame(['profile', 'posts'], $this->invokePrivate($repo, 'requestQueryAllowedIncludes'));
        $this->assertSame(['name'], $this->invokePrivate($repo, 'requestQueryAllowedFields'));
        $this->assertSame(['email_domain'], $this->invokePrivate($repo, 'requestQueryAllowedScopes'));
        $this->assertSame(['posts' => ['status']], $this->invokePrivate($repo, 'requestQueryAllowedRelationFilters'));
        $this->assertSame(['contact' => 'email'], $this->invokePrivate($repo, 'requestQueryFilterAliases'));
        $this->assertSame(
            ['account' => 'profile', 'articles' => 'posts'],
            $this->invokePrivate($repo, 'requestQueryRelationAliases'),
        );
        $this->assertSame(
            ['domain' => ['scope' => 'email_domain', 'parameters' => 1]],
            $this->invokePrivate($repo, 'requestQueryScopeMetadata'),
        );
        $this->assertSame(
            ['postsVotesSum' => [
                'relation' => 'posts',
                'column' => 'votes',
                'function' => 'sum',
                'attribute' => 'posts_votes_sum',
            ]],
            $this->invokePrivate($repo, 'requestQueryAggregateIncludes'),
        );
        $this->assertTrue($this->invokePrivate($repo, 'requestQueryStrictMode'));
        $this->assertSame(['contact'], $this->invokePrivate($repo, 'requestQueryVisibleFilters'));
        $this->assertSame(['domain'], $this->invokePrivate($repo, 'requestQueryVisibleScopes'));
        $this->assertSame(
            [
                'account',
                'articles',
                'accountCount',
                'accountExists',
                'articlesCount',
                'articlesExists',
                'postsVotesSum',
            ],
            $this->invokePrivate($repo, 'requestQueryVisibleIncludes'),
        );
        $this->assertSame(['articles'], $this->invokePrivate($repo, 'requestQueryVisibleRelationFilters'));
        $this->assertSame(['account', 'articles'], $this->invokePrivate($repo, 'requestQueryVisibleRelationCounts'));
        $this->assertSame('email', $this->invokePrivate($repo, 'resolveFilterAlias', ['contact']));
        $this->assertSame('profile', $this->invokePrivate($repo, 'resolveRelationAlias', ['account']));
        $this->assertSame('person@example.com', $this->invokePrivate(
            $repo,
            'normalizeFilterValue',
            ['email', '  PERSON@EXAMPLE.COM  '],
        ));
        $this->assertSame('john', $this->invokePrivate($repo, 'normalizeNamedFilterValue', ['search', '  JOHN  ']));
        $this->assertSame(['example.com'], $this->invokePrivate(
            $repo,
            'normalizeScopeParameters',
            ['domain', ['  EXAMPLE.COM  ']],
        ));
        $this->assertSame('published', $this->invokePrivate(
            $repo,
            'normalizeRelationFilterValue',
            ['articles', 'status', '  PUBLISHED  '],
        ));
        $this->assertSame(
            ['type' => 'relation', 'relation' => 'profile', 'attribute' => ''],
            $this->invokePrivate($repo, 'resolveIncludeRequest', ['account']),
        );
        $this->assertSame(
            ['type' => 'count', 'relation' => 'posts', 'attribute' => 'articles_count'],
            $this->invokePrivate($repo, 'resolveIncludeRequest', ['articlesCount']),
        );
        $this->assertSame(
            ['type' => 'exists', 'relation' => 'posts', 'attribute' => 'articles_exists'],
            $this->invokePrivate($repo, 'resolveIncludeRequest', ['articlesExists']),
        );
        $this->assertSame(
            [
                'type' => 'sum',
                'relation' => 'posts',
                'attribute' => 'posts_votes_sum',
                'column' => 'votes',
            ],
            $this->invokePrivate($repo, 'resolveIncludeRequest', ['postsVotesSum']),
        );
    }

    #[Test]
    public function it_returns_false_for_permissive_relation_guards_and_scope_mismatches(): void
    {
        $repo = (new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            ['posts'],
            false,
            ['email_domain'],
            ['posts' => ['status']],
        ))
            ->withRelationAliases(['articles' => 'posts'])
            ->withScopeMetadata([
                'domain' => ['scope' => 'email_domain', 'parameters' => 1],
            ]);

        $this->assertFalse($this->invokePrivate($repo, 'shouldApplyRelationFilter', ['comments']));
        $this->assertFalse($this->invokePrivate($repo, 'shouldApplyRelationCount', ['comments']));
        $this->assertFalse($this->invokePrivate($repo, 'shouldApplyRelationColumn', ['posts', 'votes']));
        $this->assertNull($this->invokePrivate($repo, 'resolveScopeClause', ['domain', []]));
    }

    #[Test]
    public function it_exercises_apply_helper_skip_and_guard_branches(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, ['status'], null, null, false))
            ->withAllowedFields(['name']);

        $fieldQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyFieldClauses', [$fieldQuery, ['email']]);
        $this->assertNull($fieldQuery->getQuery()->columns);

        $namedFilterQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyNamedFilterClauses', [$namedFilterQuery, ['missing' => 'value']]);
        $this->assertSame([], $namedFilterQuery->getQuery()->wheres ?? []);

        $guard = static fn (string $column): bool => $column === 'status';

        $whereQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyWhereClauses', [
            $whereQuery,
            [
                ['column' => 'email', 'operator' => '=', 'value' => 'skip'],
                ['column' => 'status', 'operator' => '=', 'value' => 'active'],
            ],
            $guard,
            null,
        ]);
        $this->assertCount(1, $whereQuery->getQuery()->wheres ?? []);

        $orWhereQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyOrWhereClauses', [
            $orWhereQuery,
            [
                ['column' => 'email', 'operator' => '=', 'value' => 'skip'],
                ['column' => 'status', 'operator' => '=', 'value' => 'active'],
            ],
            $guard,
            null,
        ]);
        $this->assertCount(1, $orWhereQuery->getQuery()->wheres ?? []);

        $whereInQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyWhereInClauses', [
            $whereInQuery,
            [
                ['column' => 'email', 'values' => ['skip']],
                ['column' => 'status', 'values' => ['active']],
            ],
            $guard,
            null,
        ]);
        $this->assertCount(1, $whereInQuery->getQuery()->wheres ?? []);

        $whereBetweenQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyWhereBetweenClauses', [
            $whereBetweenQuery,
            [
                ['column' => 'email', 'range' => [1, 2]],
                ['column' => 'status', 'range' => ['a', 'z']],
            ],
            $guard,
            null,
        ]);
        $this->assertCount(1, $whereBetweenQuery->getQuery()->wheres ?? []);

        $whereNullQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyWhereNullClauses', [$whereNullQuery, ['email', 'status'], $guard]);
        $this->assertCount(1, $whereNullQuery->getQuery()->wheres ?? []);

        $whereNotNullQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyWhereNotNullClauses', [$whereNotNullQuery, ['email', 'status'], $guard]);
        $this->assertCount(1, $whereNotNullQuery->getQuery()->wheres ?? []);
    }

    #[Test]
    public function it_covers_remaining_simple_private_helper_branches(): void
    {
        $strictRepo = new AllowedUserRepositoryStub(new UserStub, null, null, null, true);
        $this->invokePrivate($strictRepo, 'assertSupportedRequestQuery', [['where' => [], 1 => 'ignored']]);

        $repo = new AllowedUserRepositoryStub(
            new UserStub,
            ['status'],
            null,
            null,
            false,
            null,
            ['posts' => ['status']],
        );

        $whereQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyWhereClauses', [
            $whereQuery,
            [['column' => 'status', 'operator' => '=', 'value' => 'active']],
            null,
            null,
        ]);
        $this->assertCount(1, $whereQuery->getQuery()->wheres ?? []);

        $whereNullQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyWhereNullClauses', [
            $whereNullQuery,
            ['email'],
            static fn (): bool => false,
        ]);
        $this->assertSame([], $whereNullQuery->getQuery()->wheres ?? []);

        $whereNotNullQuery = UserStub::query();
        $this->invokePrivate($repo, 'applyWhereNotNullClauses', [
            $whereNotNullQuery,
            ['email'],
            static fn (): bool => false,
        ]);
        $this->assertSame([], $whereNotNullQuery->getQuery()->wheres ?? []);

        $this->assertTrue($this->invokePrivate($repo, 'shouldApplyRelationColumn', ['posts', 'status']));
    }

    #[Test]
    public function it_exercises_apply_scope_fallback_paths(): void
    {
        $permissiveScopeRepo = new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            null,
            false,
            ['missing_scope'],
        );
        $scopeQuery = UserStub::query();
        $this->invokePrivate($permissiveScopeRepo, 'applyScopeClauses', [
            $scopeQuery,
            [['name' => 'missing_scope', 'parameters' => []]],
        ]);
        $this->assertSame([], $scopeQuery->getQuery()->wheres ?? []);

        $strictScopeRepo = new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            null,
            true,
            ['missing_scope'],
        );

        try {
            $this->invokePrivate($strictScopeRepo, 'applyScopeClauses', [
                UserStub::query(),
                [['name' => 'missing_scope', 'parameters' => []]],
            ]);
            $this->fail('Expected unknown scope exception.');
        } catch (Throwable $exception) {
            $this->assertSame(
                'Scope [missing_scope] does not exist on the repository model.',
                $exception->getMessage(),
            );
        }
    }

    #[Test]
    public function it_exercises_apply_has_fallback_paths(): void
    {
        $permissiveHasRepo = new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            ['posts'],
            false,
            null,
            ['posts' => ['status']],
        );
        $hasSkippedQuery = UserStub::query();
        $this->invokePrivate($permissiveHasRepo, 'applyHasClauses', [
            $hasSkippedQuery,
            [['relation' => 'comments', 'operator' => '>=', 'count' => 1]],
        ]);
        $this->assertSame([], $hasSkippedQuery->getQuery()->wheres ?? []);

        $strictHasRepo = new AllowedUserRepositoryStub(new UserStub, null, null, ['ghost'], true);

        try {
            $this->invokePrivate($strictHasRepo, 'applyHasClauses', [
                UserStub::query(),
                [['relation' => 'ghost', 'operator' => '>=', 'count' => 1]],
            ]);
            $this->fail('Expected unknown relation exception.');
        } catch (Throwable $exception) {
            $this->assertSame(
                'Relation [ghost] does not exist on the repository model.',
                $exception->getMessage(),
            );
        }

        $permissiveUnknownHasRepo = new AllowedUserRepositoryStub(new UserStub, null, null, ['ghost'], false);
        $unknownHasQuery = UserStub::query();
        $this->invokePrivate($permissiveUnknownHasRepo, 'applyHasClauses', [
            $unknownHasQuery,
            [['relation' => 'ghost', 'operator' => '>=', 'count' => 1]],
        ]);
        $this->assertSame([], $unknownHasQuery->getQuery()->wheres ?? []);
    }

    #[Test]
    public function it_exercises_apply_relation_clause_fallback_paths(): void
    {
        $permissiveRelationRepo = new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            null,
            false,
            null,
            ['posts' => ['status']],
        );
        $relationSkippedQuery = UserStub::query();
        $this->invokePrivate($permissiveRelationRepo, 'applyRelationClauses', [
            $relationSkippedQuery,
            [[
                'relation' => 'comments',
                'where' => [],
                'orWhere' => [],
                'whereIn' => [],
                'whereBetween' => [],
                'whereNull' => [],
                'whereNotNull' => [],
            ]],
            'whereHas',
        ]);
        $this->assertSame([], $relationSkippedQuery->getQuery()->wheres ?? []);

        $strictRelationRepo = new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            null,
            true,
            null,
            ['ghost' => ['status']],
        );

        try {
            $this->invokePrivate($strictRelationRepo, 'applyRelationClauses', [
                UserStub::query(),
                [[
                    'relation' => 'ghost',
                    'where' => [],
                    'orWhere' => [],
                    'whereIn' => [],
                    'whereBetween' => [],
                    'whereNull' => [],
                    'whereNotNull' => [],
                ]],
                'whereHas',
            ]);
            $this->fail('Expected unknown relation exception.');
        } catch (Throwable $exception) {
            $this->assertSame(
                'Relation [ghost] does not exist on the repository model.',
                $exception->getMessage(),
            );
        }

        $permissiveUnknownRelationRepo = new AllowedUserRepositoryStub(
            new UserStub,
            null,
            null,
            null,
            false,
            null,
            ['ghost' => ['status']],
        );
        $unknownRelationQuery = UserStub::query();
        $this->invokePrivate($permissiveUnknownRelationRepo, 'applyRelationClauses', [
            $unknownRelationQuery,
            [[
                'relation' => 'ghost',
                'where' => [],
                'orWhere' => [],
                'whereIn' => [],
                'whereBetween' => [],
                'whereNull' => [],
                'whereNotNull' => [],
            ]],
            'whereHas',
        ]);
        $this->assertSame([], $unknownRelationQuery->getQuery()->wheres ?? []);

        try {
            $this->invokePrivate($permissiveRelationRepo, 'applyRelationClauses', [
                UserStub::query(),
                [[
                    'relation' => 'posts',
                    'where' => [],
                    'orWhere' => [],
                    'whereIn' => [],
                    'whereBetween' => [],
                    'whereNull' => [],
                    'whereNotNull' => [],
                ]],
                'invalidMethod',
            ]);
            $this->fail('Expected unsupported relation clause exception.');
        } catch (Throwable $exception) {
            $this->assertSame(
                'Unsupported relation clause method [invalidMethod].',
                $exception->getMessage(),
            );
        }
    }

    #[Test]
    public function it_handles_private_request_query_data_modes(): void
    {
        $permissiveRepo = new AllowedUserRepositoryStub(new UserStub);
        $strictRepo = new AllowedUserRepositoryStub(new UserStub, null, null, null, true);

        $this->assertSame([], $this->invokePrivate(
            $permissiveRepo,
            'requestQueryData',
            [Request::create('/', 'GET', ['filter' => 'invalid'])],
        ));

        $this->expectExceptionMessage('Request query payload must be an array.');
        $this->invokePrivate($strictRepo, 'requestQueryData', [Request::create('/', 'GET', ['filter' => 'invalid'])]);
    }

    #[Test]
    public function it_validates_supported_request_query_clauses(): void
    {
        $strictRepo = new AllowedUserRepositoryStub(new UserStub, null, null, null, true);
        $permissiveRepo = new AllowedUserRepositoryStub(new UserStub);

        $this->invokePrivate($permissiveRepo, 'assertSupportedRequestQuery', [['aggregate' => 'anything']]);
        $this->assertTrue(true);

        try {
            $this->invokePrivate($strictRepo, 'assertSupportedRequestQuery', [['aggregate' => []]]);
            $this->fail('Expected unsupported clause exception.');
        } catch (Throwable $exception) {
            $message = 'Request query clause [aggregate] is not supported. '
                .'Supported clauses: where, orWhere, whereIn, whereBetween, whereNull, whereNotNull, '
                .'fields, filters, scope, has, whereHas, orWhereHas, whereDoesntHave, '
                .'orWhereDoesntHave, with, order.';

            $this->assertSame(
                $message,
                $exception->getMessage(),
            );
        }

        $this->expectExceptionMessage('Request query clause [where] must be an array.');
        $this->invokePrivate($strictRepo, 'assertSupportedRequestQuery', [['where' => 'invalid']]);
    }

    #[Test]
    public function it_handles_private_alias_scope_and_relation_helpers(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub, ['status'], null, null, false, ['email_domain']))
            ->withAllowedFields(['name'])
            ->withFilterAliases(['contact' => 'email', 'email' => 'email'])
            ->withRelationAliases(['articles' => 'posts'])
            ->withScopeMetadata([
                'domain' => ['scope' => 'email_domain', 'parameters' => 1],
                'ignored' => ['scope' => 'active', 'parameters' => 0],
            ]);

        $plainRepo = new UserRepositoryStub(new UserStub);

        $this->assertNull($this->invokePrivate($plainRepo, 'requestQueryAllowedFields'));
        $this->assertSame(['name'], $this->invokePrivate($repo, 'requestQueryAllowedFields'));
        $this->assertSame(
            ['contact'],
            $this->invokePrivate(
                $repo,
                'visibleAliasedNames',
                [['email'], ['contact' => 'email', 'email' => 'email', 'ignored' => 'status']],
            ),
        );
        $this->assertNull($this->invokePrivate($repo, 'visibleAliasedNames', [null, ['contact' => 'email']]));
        $this->assertSame(['domain'], $this->invokePrivate($repo, 'requestQueryVisibleScopes'));
        $this->assertSame(
            ['scope' => 'email_domain', 'parameters' => ['test.com']],
            $this->invokePrivate($repo, 'resolveScopeClause', ['domain', ['test.com']]),
        );
        $this->assertTrue($this->invokePrivate($repo, 'passesColumnGuard', ['status', null]));
        $this->assertFalse($this->invokePrivate(
            $repo,
            'passesColumnGuard',
            ['email', static fn (string $column): bool => $column === 'status'],
        ));
        $this->assertTrue($this->invokePrivate($repo, 'relationExists', ['posts.user']));
        $this->assertFalse($this->invokePrivate($repo, 'relationExists', ['posts.missing']));

        $nonRelationRepo = new NonRelationRequestQueryRepositoryStub(new NonRelationUserStub);

        $this->assertFalse($this->invokePrivate($nonRelationRepo, 'relationExists', ['bogus']));
    }

    #[Test]
    public function it_handles_private_request_filter_resolution_modes(): void
    {
        $strictRepoWithoutFilters = new StrictRequestQueryRepositoryStub(new UserStub);

        $this->assertNull(
            $this->invokePrivate(new UserRepositoryStub(new UserStub), 'resolveRequestFilter', ['search']),
        );

        $this->expectExceptionMessage('Request filter [search] is not allowed. Allowed request filters: [none].');
        $this->invokePrivate($strictRepoWithoutFilters, 'resolveRequestFilter', ['search']);
    }

    private function invokePrivate(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($object, $method);

        return $reflection->invokeArgs($object, $arguments);
    }
}

class StrictRequestQueryRepositoryStub extends EloquentRepository implements AllowsRequestQueryInterface
{
    use HasAllowedRequestQuery;
    use HasRequestQuery;

    public function __construct(UserStub $model)
    {
        parent::__construct($model);
        $this->requestQueryStrict = true;
    }
}

class NonRelationRequestQueryRepositoryStub extends EloquentRepository
{
    use HasRequestQuery;
}

class NonRelationUserStub extends UserStub
{
    public function bogus(): object
    {
        return new stdClass;
    }
}
