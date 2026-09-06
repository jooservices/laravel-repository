<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use InvalidArgumentException;
use JOOservices\LaravelRepository\Exceptions\RepositoryException;
use JOOservices\LaravelRepository\Tests\Stubs\ActiveStatusCriteriaStub;
use JOOservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\NameOrIdRequestFilterStub;
use JOOservices\LaravelRepository\Tests\Stubs\PeerNameRequestFilterStub;
use JOOservices\LaravelRepository\Tests\Stubs\PostStub;
use JOOservices\LaravelRepository\Tests\Stubs\SoftUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\SoftUserStub;
use JOOservices\LaravelRepository\Tests\Stubs\TopByIdRequestFilterStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use JOOservices\LaravelRepository\Traits\HasDebug;
use JOOservices\LaravelRepository\Traits\HasOrder;
use JOOservices\LaravelRepository\Traits\HasRequestQuery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

/**
 * Regression coverage for release-blocking audit findings 1–4, 7–8.
 */
class AuditMutationAndCriteriaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mutating_helpers_affect_sql_when_global_scopes_exist(): void
    {
        $makeRepo = static function (): SoftUserRepositoryStub {
            return new class (new SoftUserStub()) extends SoftUserRepositoryStub {
                use HasDebug;
                use HasOrder;
                use HasRequestQuery;
            };
        };

        $makeRepo()->create($this->fakeSoftUserAttributes([
            'name' => 'Alpha',
            'email' => 'alpha@example.com',
            'status' => 'active',
        ]));

        $orderedSql = strtolower(
            $makeRepo()->orderBy(['name' => 'desc'])->toSql(),
        );
        $this->assertStringContainsString('order by', $orderedSql);
        $this->assertStringContainsString('name', $orderedSql);
        $this->assertStringContainsString('deleted_at', $orderedSql);

        $lockedSql = strtolower(
            $makeRepo()->filter(['status' => 'active'])->lockForUpdate()->toSql(),
        );
        $this->assertStringContainsString('status', $lockedSql);
        $this->assertStringContainsString('deleted_at', $lockedSql);

        $request = Request::create('/soft-users', 'GET', [
            'filter' => [
                'whereIn' => [
                    ['column' => 'status', 'values' => ['active', 'pending']],
                ],
                'fields' => ['name', 'email'],
            ],
        ]);

        $projectedSql = strtolower($makeRepo()->fromRequest($request)->toSql());
        $this->assertStringContainsString('select', $projectedSql);
        $this->assertStringContainsString('name', $projectedSql);
        $this->assertStringContainsString('soft_users"."id', $projectedSql);
        $this->assertStringContainsString('in (', $projectedSql);
        $this->assertStringContainsString('deleted_at', $projectedSql);
    }

    #[Test]
    public function request_or_where_cannot_bypass_tenant_criteria(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());
        $tenantStatus = 'active';

        $repo->create($this->fakeUserAttributes([
            'name' => 'Tenant',
            'email' => 'tenant@example.com',
            'status' => $tenantStatus,
        ]));
        $repo->create($this->fakeUserAttributes([
            'name' => 'Other',
            'email' => 'other@example.com',
            'status' => 'pending',
        ]));

        $repo->pushCriteria(new ActiveStatusCriteriaStub());

        $request = Request::create('/users', 'GET', [
            'filter' => [
                'where' => [
                    ['name', 'Tenant'],
                ],
                'orWhere' => [
                    ['status', 'pending'],
                ],
            ],
        ]);

        $results = $repo->fromRequest($request)->get();

        // Without grouping, SQL would be: status=active AND name=Tenant OR status=pending
        // (escaping criteria). With grouping: status=active AND (name=Tenant OR status=pending).
        $this->assertCount(1, $results);
        $this->assertSame($tenantStatus, $results->first()->status);
        $this->assertSame('Tenant', $results->first()->name);
    }

    #[Test]
    public function criteria_weak_map_reapplies_after_builder_is_destroyed(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());
        $applications = 0;

        $criteria = new class ($applications) implements \JOOservices\LaravelRepository\Contracts\CriteriaInterface {
            public function __construct(private int &$applications)
            {
            }

            public function apply(\Illuminate\Database\Eloquent\Builder $query): void
            {
                $this->applications++;
                $query->where('status', 'active');
            }
        };

        $repo->pushCriteria($criteria);

        $getQuery = new ReflectionMethod($repo, 'getQuery');
        $first = $getQuery->invoke($repo);
        $firstId = spl_object_id($first);
        $this->assertSame(1, $applications);

        $queryProperty = new ReflectionProperty($repo, 'query');
        $queryProperty->setValue($repo, null);
        unset($first);
        gc_collect_cycles();

        $second = $getQuery->invoke($repo);
        $this->assertSame(2, $applications);
        $this->assertNotSame($firstId, spl_object_id($second));

        // Simulate recycled object id: mark a fresh builder under the old id path
        // by applying criteria twice on distinct builders tracked via WeakMap.
        $third = UserStub::query();
        $repo->applyCriteria($third);
        $repo->applyCriteria($third);
        $this->assertSame(3, $applications);
    }

    #[Test]
    public function update_or_create_respects_criteria_and_upsert_throws_with_criteria(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());

        $pending = $repo->create($this->fakeUserAttributes([
            'name' => 'Pending',
            'email' => 'pending-criteria@example.com',
            'status' => 'pending',
            'score' => 1,
        ]));
        $active = $repo->create($this->fakeUserAttributes([
            'name' => 'Active',
            'email' => 'active-criteria@example.com',
            'status' => 'active',
            'score' => 2,
        ]));

        $repo->pushCriteria(new ActiveStatusCriteriaStub());

        $updated = $repo->updateOrCreate(
            ['email' => 'active-criteria@example.com'],
            ['name' => 'Active Updated', 'score' => 99],
        );

        $this->assertSame($active->id, $updated->id);
        $this->assertSame('Active Updated', $updated->name);
        $this->assertSame('Pending', $repo->clearCriteria()->findOrFail($pending->id)->name);

        $repo->pushCriteria(new ActiveStatusCriteriaStub());

        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Cannot upsert while repository criteria are active');
        $repo->upsert(
            [[
                'id' => $active->id,
                'name' => 'Bulk',
                'email' => 'active-criteria@example.com',
                'status' => 'active',
                'score' => 0,
            ]],
            ['id'],
            ['name', 'score'],
        );
    }

    #[Test]
    public function cursor_pagination_with_duplicate_sort_values_returns_all_pages(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());
        $sharedName = 'DupName';

        $first = $repo->create($this->fakeUserAttributes([
            'name' => $sharedName,
            'status' => 'active',
        ]));
        $second = $repo->create($this->fakeUserAttributes([
            'name' => $sharedName,
            'status' => 'active',
        ]));
        $third = $repo->create($this->fakeUserAttributes([
            'name' => $sharedName,
            'status' => 'active',
        ]));

        $page1 = $repo->orderBy(['name' => 'asc'])->cursorPaginate(2);
        $this->assertCount(2, $page1->items());

        $nextCursor = $page1->nextCursor();
        $this->assertNotNull($nextCursor);

        $page2 = $repo->orderBy(['name' => 'asc'])->cursorPaginate(2, ['*'], 'cursor', $nextCursor->encode());
        $this->assertCount(1, $page2->items());

        $seenIds = [
            ...array_map(static fn(UserStub $user): int => (int) $user->id, $page1->items()),
            ...array_map(static fn(UserStub $user): int => (int) $user->id, $page2->items()),
        ];

        $this->assertEqualsCanonicalizing(
            [(int) $first->id, (int) $second->id, (int) $third->id],
            $seenIds,
        );
    }

    #[Test]
    public function cache_key_distinguishes_dotted_array_segments(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());

        $keyA = $repo->cacheKey('list', [['a.b', 'c']]);
        $keyB = $repo->cacheKey('list', [['a', 'b.c']]);

        $this->assertNotSame($keyA, $keyB);
        $this->assertStringContainsString('list', $keyA);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', substr($keyA, strrpos($keyA, '.') + 1));
    }

    #[Test]
    public function cache_key_encodes_datetime_values_not_object_ids(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());

        $early = new DateTimeImmutable('2020-01-01T00:00:00+00:00');
        $late = new DateTimeImmutable('2030-01-01T00:00:00+00:00');

        $this->assertNotSame(
            $repo->cacheKey('range', [$early]),
            $repo->cacheKey('range', [$late]),
        );
        $this->assertSame(
            $repo->cacheKey('range', [new DateTimeImmutable('2020-01-01T00:00:00+00:00')]),
            $repo->cacheKey('range', [new DateTimeImmutable('2020-01-01T00:00:00+00:00')]),
        );
    }

    #[Test]
    public function cache_key_rejects_unsupported_objects(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported cache key object');
        $repo->cacheKey('bad', [new stdClass()]);
    }

    #[Test]
    public function cursor_pagination_merges_order_columns_into_sparse_projection(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());
        $shared = fake()->unique()->userName();

        $first = $repo->create($this->fakeUserAttributes([
            'name' => $shared,
            'score' => 10,
            'status' => 'active',
        ]));
        $second = $repo->create($this->fakeUserAttributes([
            'name' => $shared,
            'score' => 20,
            'status' => 'active',
        ]));

        $page1 = $repo->orderBy(['score' => 'asc'])->cursorPaginate(1, ['name']);
        $this->assertCount(1, $page1->items());
        $this->assertSame((int) $first->id, (int) $page1->items()[0]->id);

        $nextCursor = $page1->nextCursor();
        $this->assertNotNull($nextCursor);

        $page2 = $repo->orderBy(['score' => 'asc'])->cursorPaginate(
            1,
            ['name'],
            'cursor',
            $nextCursor->encode(),
        );
        $this->assertCount(1, $page2->items());
        $this->assertSame((int) $second->id, (int) $page2->items()[0]->id);
    }

    #[Test]
    public function named_filter_joins_apply_on_outer_query(): void
    {
        $name = fake()->unique()->name();
        $repo = (new AllowedUserRepositoryStub(new UserStub()))
            ->withRequestFilters(['peer_name' => PeerNameRequestFilterStub::class]);

        $matched = $repo->create($this->fakeUserAttributes(['name' => $name, 'status' => 'active']));
        $repo->create($this->fakeUserAttributes(['name' => fake()->unique()->name(), 'status' => 'active']));

        $results = $repo->fromRequest(Request::create('/', 'GET', [
            'filter' => [
                'filters' => ['peer_name' => $name],
            ],
        ]))->get();

        $this->assertCount(1, $results);
        $this->assertSame((int) $matched->id, (int) $results->first()->id);
    }

    #[Test]
    public function named_filter_or_where_cannot_bypass_criteria(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub()))
            ->withRequestFilters(['name_or_id' => NameOrIdRequestFilterStub::class]);

        $active = $repo->create($this->fakeUserAttributes([
            'name' => 'Active User',
            'status' => 'active',
        ]));
        $pending = $repo->create($this->fakeUserAttributes([
            'name' => 'Pending User',
            'status' => 'pending',
        ]));

        $repo->pushCriteria(new ActiveStatusCriteriaStub());

        $results = $repo->fromRequest(Request::create('/', 'GET', [
            'filter' => [
                'filters' => [
                    'name_or_id' => [
                        'name' => 'missing-name',
                        'id' => (int) $pending->id,
                    ],
                ],
            ],
        ]))->get();

        $this->assertCount(0, $results);
        $this->assertDatabaseHas('users', ['id' => $active->id, 'status' => 'active']);
        $this->assertDatabaseHas('users', ['id' => $pending->id, 'status' => 'pending']);
    }

    #[Test]
    public function cache_key_distinguishes_datetime_microseconds(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());

        $early = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', '2020-01-01 12:00:00.100000');
        $late = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', '2020-01-01 12:00:00.900000');
        $this->assertNotFalse($early);
        $this->assertNotFalse($late);

        $this->assertNotSame(
            $repo->cacheKey('range', [$early]),
            $repo->cacheKey('range', [$late]),
        );
    }

    #[Test]
    public function cursor_pagination_merges_builder_projection_with_explicit_columns(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());
        $shared = fake()->unique()->userName();

        $first = $repo->create($this->fakeUserAttributes([
            'name' => $shared,
            'score' => 10,
            'status' => 'active',
        ]));
        $second = $repo->create($this->fakeUserAttributes([
            'name' => $shared,
            'score' => 20,
            'status' => 'active',
        ]));

        $getQuery = new ReflectionMethod($repo, 'getQuery');
        /** @var \Illuminate\Database\Eloquent\Builder<UserStub> $query */
        $query = $getQuery->invoke($repo->orderBy(['score' => 'asc']));
        $query->select(['name']);

        $page1 = $repo->cursorPaginate(1, ['name']);
        $this->assertCount(1, $page1->items());
        $this->assertSame((int) $first->id, (int) $page1->items()[0]->id);

        $nextCursor = $page1->nextCursor();
        $this->assertNotNull($nextCursor);
        $this->assertNotContains(null, $nextCursor->toArray());

        $query2 = $getQuery->invoke($repo->orderBy(['score' => 'asc']));
        $query2->select(['name']);

        $page2 = $repo->cursorPaginate(1, ['name'], 'cursor', $nextCursor->encode());
        $this->assertCount(1, $page2->items());
        $this->assertSame((int) $second->id, (int) $page2->items()[0]->id);
    }

    #[Test]
    public function cursor_pagination_with_join_uses_qualified_primary_key(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());
        $name = fake()->unique()->name();

        $first = $repo->create($this->fakeUserAttributes(['name' => $name, 'status' => 'active']));
        $second = $repo->create($this->fakeUserAttributes(['name' => $name, 'status' => 'active']));

        $attachJoin = static function (AllowedUserRepositoryStub $repository) use ($name): void {
            $getQuery = new ReflectionMethod($repository, 'getQuery');
            /** @var \Illuminate\Database\Eloquent\Builder<UserStub> $query */
            $query = $getQuery->invoke($repository);
            $query->getQuery()->join('users as peer', 'peer.id', '=', 'users.id');
            $query->where('peer.name', '=', $name);
            $query->orderBy('users.id');
        };

        $attachJoin($repo);
        $page1 = $repo->cursorPaginate(1, ['users.name']);
        $this->assertCount(1, $page1->items());
        $this->assertSame((int) $first->id, (int) $page1->items()[0]->id);

        $nextCursor = $page1->nextCursor();
        $this->assertNotNull($nextCursor);

        $attachJoin($repo);
        $page2 = $repo->cursorPaginate(1, ['users.name'], 'cursor', $nextCursor->encode());
        $this->assertCount(1, $page2->items());
        $this->assertSame((int) $second->id, (int) $page2->items()[0]->id);
    }

    #[Test]
    public function named_filter_preserves_select_order_and_limit(): void
    {
        $repo = (new AllowedUserRepositoryStub(new UserStub()))
            ->withRequestFilters(['top' => TopByIdRequestFilterStub::class]);

        $repo->create($this->fakeUserAttributes(['name' => 'First', 'status' => 'active']));
        $repo->create($this->fakeUserAttributes(['name' => 'Second', 'status' => 'active']));
        $third = $repo->create($this->fakeUserAttributes(['name' => 'Third', 'status' => 'active']));

        $results = $repo->fromRequest(Request::create('/', 'GET', [
            'filter' => [
                'filters' => ['top' => true],
            ],
        ]))->get();

        $this->assertCount(1, $results);
        $this->assertSame((int) $third->id, (int) $results->first()->id);
        $this->assertSame('Third', $results->first()->name);
        $this->assertFalse(isset($results->first()->getAttributes()['email']));
    }

    #[Test]
    public function cursor_pagination_preserves_with_count_expression_columns(): void
    {
        $repo = new AllowedUserRepositoryStub(new UserStub());
        $user = $repo->create($this->fakeUserAttributes(['status' => 'active']));

        PostStub::query()->create([
            'user_id' => $user->id,
            'author_id' => $user->id,
            'title' => fake()->sentence(3),
            'status' => 'published',
            'votes' => 1,
        ]);
        PostStub::query()->create([
            'user_id' => $user->id,
            'author_id' => $user->id,
            'title' => fake()->sentence(3),
            'status' => 'published',
            'votes' => 2,
        ]);

        $ordinary = UserStub::query()->withCount('posts')->findOrFail($user->id);
        $this->assertSame(2, (int) $ordinary->posts_count);

        $getQuery = new ReflectionMethod($repo, 'getQuery');
        /** @var \Illuminate\Database\Eloquent\Builder<UserStub> $query */
        $query = $getQuery->invoke($repo);
        $query->withCount('posts');

        $page = $repo->cursorPaginate(1);
        $this->assertCount(1, $page->items());
        $this->assertSame(2, (int) $page->items()[0]->posts_count);
    }
}
