<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelRepository\Console\Commands\MakeRepositoryCommand;
use JOOservices\LaravelRepository\Contracts\ReadRepositoryInterface;
use JOOservices\LaravelRepository\Contracts\RepositoryInterface;
use JOOservices\LaravelRepository\Repositories\Presets\ReadRepository;
use JOOservices\LaravelRepository\Tests\Stubs\AllowedUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\ProfileUserRepositoryStub;
use JOOservices\LaravelRepository\Tests\Stubs\UserStub;
use JOOservices\LaravelRepository\Tests\TestCase;
use JOOservices\LaravelRepository\Traits\HasRequestQuery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Regression coverage for release-blocking audit findings 5–6, 9–11.
 */
class AuditReadScopeAndStubTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function relation_exists_does_not_invoke_non_relation_public_methods(): void
    {
        $model = new class extends UserStub {
            public bool $sideEffectCalled = false;

            public function notARelation(): string
            {
                $this->sideEffectCalled = true;

                return 'side-effect';
            }

            /**
             * @return HasMany<UserStub, $this>
             */
            public function posts(): HasMany
            {
                return $this->hasMany(UserStub::class, 'id', 'id');
            }
        };

        $repo = new class ($model) extends AllowedUserRepositoryStub {
        };

        $exists = new ReflectionMethod($repo, 'relationExists');
        $this->assertFalse($exists->invoke($repo, 'notARelation'));
        $this->assertFalse($model->sideEffectCalled);
        $this->assertTrue($exists->invoke($repo, 'posts'));
    }

    #[Test]
    public function concrete_read_repository_is_instantiable_without_crud(): void
    {
        $repo = new class (new UserStub()) extends ReadRepository {
        };

        $this->assertInstanceOf(ReadRepositoryInterface::class, $repo);
        $this->assertNotInstanceOf(RepositoryInterface::class, $repo);
        $this->assertFalse(method_exists($repo, 'create'));
        $this->assertFalse(method_exists($repo, 'update'));
        $this->assertFalse(method_exists($repo, 'delete'));
        $this->assertSame(0, $repo->count());
    }

    #[Test]
    public function for_profile_clears_omitted_allowlists_on_switch(): void
    {
        $repo = (new ProfileUserRepositoryStub(new UserStub()))
            ->withQueryProfiles([
                'admin' => [
                    'filters' => ['status', 'email'],
                    'sorts' => ['name', 'id'],
                    'includes' => ['posts'],
                    'strict' => true,
                ],
                'public' => [
                    'filters' => ['status'],
                    'sorts' => ['name'],
                    'strict' => false,
                ],
            ]);

        $repo->forProfile('admin');
        $this->assertSame(['posts'], $repo->allowedIncludes());

        $repo->forProfile('public');

        $this->assertSame(['status'], $repo->allowedFilters());
        $this->assertSame(['name'], $repo->allowedSorts());
        $this->assertNull($repo->allowedIncludes());
        $this->assertFalse($repo->isRequestQueryStrict());
    }

    #[Test]
    public function for_profile_preserves_baseline_restrictions_when_profile_omits_keys(): void
    {
        $repo = (new ProfileUserRepositoryStub(new UserStub()))
            ->withAllowedIncludes([])
            ->withRequestQueryStrict(true)
            ->withQueryProfiles([
                'public' => [
                    'filters' => ['status'],
                    'sorts' => ['name'],
                ],
            ]);

        $repo->forProfile('public');

        $this->assertSame(['status'], $repo->allowedFilters());
        $this->assertSame(['name'], $repo->allowedSorts());
        $this->assertSame([], $repo->allowedIncludes());
        $this->assertTrue($repo->isRequestQueryStrict());
    }

    #[Test]
    public function untyped_relation_is_accepted_when_explicitly_allowlisted(): void
    {
        $model = new class extends UserStub {
            public function legacyNotes()
            {
                return $this->hasMany(UserStub::class, 'id', 'id');
            }
        };

        $denied = new AllowedUserRepositoryStub($model, null, null, null, false);
        $allowed = new AllowedUserRepositoryStub($model, null, null, ['legacyNotes'], false);

        $exists = new ReflectionMethod(AllowedUserRepositoryStub::class, 'relationExists');
        $this->assertFalse($exists->invoke($denied, 'legacyNotes'));
        $this->assertTrue($exists->invoke($allowed, 'legacyNotes'));
    }

    #[Test]
    public function untyped_relation_is_accepted_via_trusted_relations(): void
    {
        $model = new class extends UserStub {
            public function legacyNotes()
            {
                return $this->hasMany(UserStub::class, 'id', 'id');
            }
        };

        $repo = (new AllowedUserRepositoryStub($model, null, null, null, false))
            ->withTrustedRelations(['legacyNotes']);

        $exists = new ReflectionMethod($repo, 'relationExists');
        $this->assertTrue($exists->invoke($repo, 'legacyNotes'));
    }

    #[Test]
    public function trusted_relation_does_not_authorize_untyped_descendants(): void
    {
        $model = new class extends UserStub {
            public static int $auditEffectCalls = 0;

            public function peers()
            {
                return $this->hasMany(self::class, 'id', 'id');
            }

            public function auditEffect()
            {
                self::$auditEffectCalls++;

                return 'not-a-relation';
            }
        };
        $model::$auditEffectCalls = 0;

        $repo = (new AllowedUserRepositoryStub($model, null, null, null, false))
            ->withTrustedRelations(['peers']);

        $exists = new ReflectionMethod($repo, 'relationExists');
        $this->assertTrue($exists->invoke($repo, 'peers'));
        $this->assertFalse($exists->invoke($repo, 'peers.auditEffect'));
        $this->assertSame(0, $model::$auditEffectCalls);
    }

    #[Test]
    public function attribute_scope_is_detected_by_scope_exists(): void
    {
        if (! class_exists(Scope::class)) {
            $this->markTestSkipped('Illuminate Scope attribute is unavailable.');
        }

        $model = new class extends UserStub {
            /**
             * @param  Builder<UserStub>  $query
             */
            #[Scope]
            protected function featured(Builder $query): void
            {
                $query->where('status', 'featured');
            }
        };

        $repo = new class ($model) {
            use HasRequestQuery;

            public function __construct(private UserStub $model)
            {
            }

            public function getModel(): UserStub
            {
                return $this->model;
            }
        };

        $scopeExists = new ReflectionMethod($repo, 'scopeExists');
        $this->assertTrue($scopeExists->invoke($repo, 'featured'));
        $this->assertTrue($scopeExists->invoke($repo, 'active'));
        $this->assertFalse($scopeExists->invoke($repo, 'missingScope'));
    }

    #[Test]
    public function get_stub_resolves_published_laravel_repository_path(): void
    {
        $command = $this->app->make(MakeRepositoryCommand::class);
        $filename = 'repository.read.stub';
        $publishedDir = base_path('stubs/laravel-repository');
        $publishedPath = $publishedDir . '/' . $filename;

        if (! is_dir($publishedDir)) {
            mkdir($publishedDir, 0777, true);
        }

        $marker = '// published-read-stub-' . fake()->uuid();
        file_put_contents($publishedPath, $marker);

        try {
            $command->setLaravel($this->app);
            $input = new \Symfony\Component\Console\Input\ArrayInput([
                'name' => 'TempPublishedReadRepository',
                '--preset' => 'read',
            ]);
            $input->bind($command->getDefinition());

            $definitionProperty = new ReflectionProperty($command, 'input');
            $definitionProperty->setValue($command, $input);

            $getStub = new ReflectionMethod($command, 'getStub');
            $resolved = $getStub->invoke($command);

            $this->assertSame($publishedPath, $resolved);
            $this->assertSame($marker, (string) file_get_contents($resolved));
        } finally {
            if (is_file($publishedPath)) {
                unlink($publishedPath);
            }
            if (is_dir($publishedDir) && scandir($publishedDir) === ['.', '..']) {
                rmdir($publishedDir);
            }
        }
    }

    #[Test]
    public function read_repository_interface_is_not_crud_contract(): void
    {
        $reflection = new ReflectionClass(ReadRepositoryInterface::class);
        $this->assertTrue($reflection->isInterface());

        $methods = array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(),
        );

        $this->assertNotContains('create', $methods);
        $this->assertNotContains('update', $methods);
        $this->assertNotContains('delete', $methods);
        $this->assertContains('fromRequest', $methods);
        $this->assertContains('get', $methods);
        $this->assertContains('count', $methods);
    }
}
