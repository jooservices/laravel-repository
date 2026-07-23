<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Jooservices\LaravelRepository\Tests\Stubs\CommentRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\CommentStub;
use Jooservices\LaravelRepository\Tests\Stubs\PostRepositoryStub;
use Jooservices\LaravelRepository\Tests\Stubs\PostStub;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FieldProjectionEagerLoadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function fields_with_belongs_to_include_preserve_foreign_key_in_permissive_mode(): void
    {
        $user = UserStub::query()->create([
            'name' => 'Owner',
            'email' => 'owner@x.com',
            'status' => 'active',
        ]);
        PostStub::query()->create([
            'user_id' => $user->id,
            'title' => 'Projected',
            'status' => 'published',
            'votes' => 1,
        ]);

        $repo = (new PostRepositoryStub(new PostStub))
            ->withAllowedFields(['title'])
            ->withAllowedIncludes(['user']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['title'],
                'with' => ['user'],
            ],
        ]);

        $post = $repo->fromRequest($request)->get()->first();

        $this->assertNotNull($post);
        $this->assertSame('Projected', $post->title);
        $this->assertArrayHasKey('user_id', $post->getAttributes());
        $this->assertTrue($post->relationLoaded('user'));
        $this->assertNotNull($post->user);
        $this->assertSame($user->id, $post->user->id);
        $this->assertSame('Owner', $post->user->name);
    }

    #[Test]
    public function fields_with_belongs_to_include_preserve_foreign_key_in_strict_mode_without_allowlisting_fk(): void
    {
        $user = UserStub::query()->create([
            'name' => 'Strict Owner',
            'email' => 'strict-owner@x.com',
            'status' => 'active',
        ]);
        PostStub::query()->create([
            'user_id' => $user->id,
            'title' => 'Strict Projected',
            'status' => 'published',
            'votes' => 2,
        ]);

        $repo = (new PostRepositoryStub(new PostStub))
            ->withAllowedFields(['title'])
            ->withAllowedIncludes(['user'])
            ->withStrictMode(true);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['title'],
                'with' => ['user'],
            ],
        ]);

        $post = $repo->fromRequest($request)->get()->first();

        $this->assertNotNull($post);
        $this->assertTrue($post->relationLoaded('user'));
        $this->assertNotNull($post->user);
        $this->assertSame($user->id, $post->user->id);
        $this->assertArrayHasKey('user_id', $post->getAttributes());
        $this->assertArrayNotHasKey('status', $post->getAttributes());
    }

    #[Test]
    public function fields_with_custom_foreign_key_belongs_to_preserve_author_id(): void
    {
        $author = UserStub::query()->create([
            'name' => 'Author',
            'email' => 'author@x.com',
            'status' => 'active',
        ]);
        $owner = UserStub::query()->create([
            'name' => 'Owner Two',
            'email' => 'owner2@x.com',
            'status' => 'active',
        ]);
        PostStub::query()->create([
            'user_id' => $owner->id,
            'author_id' => $author->id,
            'title' => 'Custom FK',
            'status' => 'published',
            'votes' => 3,
        ]);

        $repo = (new PostRepositoryStub(new PostStub))
            ->withAllowedFields(['title'])
            ->withAllowedIncludes(['author']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['title'],
                'with' => ['author'],
            ],
        ]);

        $post = $repo->fromRequest($request)->get()->first();

        $this->assertNotNull($post);
        $this->assertArrayHasKey('author_id', $post->getAttributes());
        $this->assertTrue($post->relationLoaded('author'));
        $this->assertNotNull($post->author);
        $this->assertSame($author->id, $post->author->id);
        $this->assertSame('Author', $post->author->name);
    }

    #[Test]
    public function fields_with_morph_to_include_preserve_morph_keys(): void
    {
        $user = UserStub::query()->create([
            'name' => 'Commentable',
            'email' => 'commentable@x.com',
            'status' => 'active',
        ]);
        CommentStub::query()->create([
            'body' => 'Hello morph',
            'commentable_id' => $user->id,
            'commentable_type' => UserStub::class,
        ]);

        $repo = (new CommentRepositoryStub(new CommentStub))
            ->withAllowedFields(['body'])
            ->withAllowedIncludes(['commentable']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['body'],
                'with' => ['commentable'],
            ],
        ]);

        $comment = $repo->fromRequest($request)->get()->first();

        $this->assertNotNull($comment);
        $this->assertSame('Hello morph', $comment->body);
        $this->assertArrayHasKey('commentable_id', $comment->getAttributes());
        $this->assertArrayHasKey('commentable_type', $comment->getAttributes());
        $this->assertTrue($comment->relationLoaded('commentable'));
        $this->assertNotNull($comment->commentable);
        $this->assertSame($user->id, $comment->commentable->id);
        $this->assertSame('Commentable', $comment->commentable->name);
    }

    #[Test]
    public function fields_without_includes_do_not_force_foreign_keys(): void
    {
        $user = UserStub::query()->create([
            'name' => 'Solo',
            'email' => 'solo@x.com',
            'status' => 'active',
        ]);
        PostStub::query()->create([
            'user_id' => $user->id,
            'title' => 'No Include',
            'status' => 'published',
            'votes' => 0,
        ]);

        $repo = (new PostRepositoryStub(new PostStub))->withAllowedFields(['title']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['title'],
            ],
        ]);

        $post = $repo->fromRequest($request)->get()->first();

        $this->assertNotNull($post);
        $this->assertSame(['id', 'title'], array_keys($post->getAttributes()));
    }

    #[Test]
    public function aggregate_includes_do_not_force_belongs_to_foreign_keys(): void
    {
        $user = UserStub::query()->create([
            'name' => 'Counter',
            'email' => 'counter@x.com',
            'status' => 'active',
        ]);
        PostStub::query()->create([
            'user_id' => $user->id,
            'title' => 'Counted',
            'status' => 'published',
            'votes' => 4,
        ]);

        // withCount does not hydrate the relation model; FK preserve is not required.
        $repo = (new PostRepositoryStub(new PostStub))
            ->withAllowedFields(['title'])
            ->withAllowedIncludes(['userCount']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['title'],
                'with' => ['userCount'],
            ],
        ]);

        $post = $repo->fromRequest($request)->get()->first();

        $this->assertNotNull($post);
        $this->assertSame(['id', 'title'], array_keys(array_filter(
            $post->getAttributes(),
            static fn (mixed $value, string $key): bool => $key !== 'user_count',
            ARRAY_FILTER_USE_BOTH,
        )));
        $this->assertSame(1, (int) $post->user_count);
    }

    #[Test]
    public function fields_with_unknown_include_skip_owner_key_preservation_in_permissive_mode(): void
    {
        $user = UserStub::query()->create([
            'name' => 'Ghost',
            'email' => 'ghost@x.com',
            'status' => 'active',
        ]);
        PostStub::query()->create([
            'user_id' => $user->id,
            'title' => 'Unknown Include',
            'status' => 'published',
            'votes' => 0,
        ]);

        $repo = (new PostRepositoryStub(new PostStub))
            ->withAllowedFields(['title'])
            ->withAllowedIncludes(['missingRelation']);

        $request = Request::create('/', 'GET', [
            'filter' => [
                'fields' => ['title'],
                'with' => ['missingRelation'],
            ],
        ]);

        $post = $repo->fromRequest($request)->get()->first();

        $this->assertNotNull($post);
        $this->assertSame(['id', 'title'], array_keys($post->getAttributes()));
        $this->assertFalse($post->relationLoaded('missingRelation'));
    }
}
