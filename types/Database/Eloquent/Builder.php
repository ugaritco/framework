<?php

namespace Heritage\Types\Builder;

use Heritage\Database\Eloquent\Builder;
use Heritage\Database\Eloquent\HasBuilder;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Relations\BelongsTo;
use Heritage\Database\Eloquent\Relations\HasMany;
use Heritage\Database\Eloquent\Relations\MorphTo;
use Heritage\Database\Query\Builder as QueryBuilder;

use function PHPStan\Testing\assertType;

/** @param \Heritage\Database\Eloquent\Builder<User> $query */
function test(
    Builder $query,
    User $user,
    Post $post,
    ChildPost $childPost,
    Comment $comment,
    QueryBuilder $queryBuilder
): void {
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->where('id', 1));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhere('name', 'John'));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereNot('status', 'active'));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->with('relation'));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->with(['relation' => ['foo' => fn ($q) => $q]]));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->with(['relation' => function ($query) {
        // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
    }]));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->without('relation'));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->withOnly(['relation']));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->withOnly(['relation' => ['foo' => fn ($q) => $q]]));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->withOnly(['relation' => function ($query) {
        // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
    }]));
    assertType('array<int, Heritage\Types\Builder\User>', $query->getModels());
    assertType('array<int, Heritage\Types\Builder\User>', $query->eagerLoadRelations([]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Builder\User>', $query->get());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Builder\User>', $query->hydrate([]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Builder\User>', $query->fromQuery('foo', []));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Builder\User>', $query->findMany([1, 2, 3]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Builder\User>', $query->findOrFail([1]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Builder\User>', $query->findOrNew([1]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Builder\User>', $query->find([1]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Builder\User>', $query->findOr([1], callback: fn () => 42));
    assertType('Heritage\Types\Builder\User', $query->findOrFail(1));
    assertType('Heritage\Types\Builder\User|null', $query->find(1));
    assertType('42|Heritage\Types\Builder\User', $query->findOr(1, fn () => 42));
    assertType('42|Heritage\Types\Builder\User', $query->findOr(1, callback: fn () => 42));
    assertType('Heritage\Types\Builder\User|null', $query->first());
    assertType('42|Heritage\Types\Builder\User', $query->firstOr(fn () => 42));
    assertType('42|Heritage\Types\Builder\User', $query->firstOr(callback: fn () => 42));
    assertType('Heritage\Types\Builder\User', $query->firstOrNew(['id' => 1]));
    assertType('Heritage\Types\Builder\User', $query->findOrNew(1));
    assertType('Heritage\Types\Builder\User', $query->firstOrCreate(['id' => 1]));
    assertType('Heritage\Types\Builder\User', $query->create(['name' => 'John']));
    assertType('Heritage\Types\Builder\User', $query->forceCreate(['name' => 'John']));
    assertType('Heritage\Types\Builder\User', $query->forceCreateQuietly(['name' => 'John']));
    assertType('Heritage\Types\Builder\User', $query->getModel());
    assertType('Heritage\Types\Builder\User', $query->make(['name' => 'John']));
    assertType('Heritage\Types\Builder\User', $query->forceCreate(['name' => 'John']));
    assertType('Heritage\Types\Builder\User', $query->updateOrCreate(['id' => 1], ['name' => 'John']));
    assertType('Heritage\Types\Builder\User', $query->firstOrFail());
    assertType('Heritage\Types\Builder\User', $query->findSole(1));
    assertType('Heritage\Types\Builder\User', $query->sole());
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Builder\User>', $query->cursor());
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Builder\User>', $query->cursor());
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Builder\User>', $query->lazy());
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Builder\User>', $query->lazyById());
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Builder\User>', $query->lazyByIdDesc());
    assertType('Heritage\Support\Collection<(int|string), mixed>', $query->pluck('foo'));
    assertType('Heritage\Database\Eloquent\Relations\Relation<Heritage\Database\Eloquent\Model, Heritage\Types\Builder\User, *>', $query->getRelation('foo'));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query->setModel(new Post()));

    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->has('foo', callback: function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->has($user->posts(), callback: function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orHas($user->posts()));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->doesntHave($user->posts(), callback: function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orDoesntHave($user->posts()));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereHas($user->posts(), function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->withWhereHas('posts', function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<*>|Heritage\Database\Eloquent\Relations\Relation<*, *, *>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereHas($user->posts(), function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereDoesntHave($user->posts(), function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereDoesntHave($user->posts(), function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->hasMorph($post->taggable(), 'taggable', callback: function ($query, $type) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orHasMorph($post->taggable(), 'taggable'));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->doesntHaveMorph($post->taggable(), 'taggable', callback: function ($query, $type) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orDoesntHaveMorph($post->taggable(), 'taggable'));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereHasMorph($post->taggable(), 'taggable', function ($query, $type) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereHasMorph($post->taggable(), 'taggable', function ($query, $type) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereDoesntHaveMorph($post->taggable(), 'taggable', function ($query, $type) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereDoesntHaveMorph($post->taggable(), 'taggable', function ($query, $type) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
        assertType('string', $type);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereRelation($user->posts(), function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereRelation($user->posts(), function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereDoesntHaveRelation($user->posts(), function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereDoesntHaveRelation($user->posts(), function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\Post>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereMorphRelation($post->taggable(), 'taggable', function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereMorphRelation($post->taggable(), 'taggable', function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereMorphDoesntHaveRelation($post->taggable(), 'taggable', function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereMorphDoesntHaveRelation($post->taggable(), 'taggable', function ($query) {
        assertType('Heritage\Database\Eloquent\Builder<Heritage\Database\Eloquent\Model>', $query);
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereMorphedTo($post->taggable(), new Post()));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->whereNotMorphedTo($post->taggable(), new Post()));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereMorphedTo($post->taggable(), new Post()));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->orWhereNotMorphedTo($post->taggable(), new Post()));

    $query->chunk(1, function ($users, $page) {
        assertType('Heritage\Support\Collection<int, Heritage\Types\Builder\User>', $users);
        assertType('int', $page);
    });
    $query->chunkById(1, function ($users, $page) {
        assertType('Heritage\Support\Collection<int, Heritage\Types\Builder\User>', $users);
        assertType('int', $page);
    });
    $query->chunkMap(function ($users) {
        assertType('Heritage\Types\Builder\User', $users);
    });
    $query->chunkByIdDesc(1, function ($users, $page) {
        assertType('Heritage\Support\Collection<int, Heritage\Types\Builder\User>', $users);
        assertType('int', $page);
    });
    $query->each(function ($users, $page) {
        assertType('Heritage\Types\Builder\User', $users);
        assertType('int', $page);
    });
    $query->eachById(function ($users, $page) {
        assertType('Heritage\Types\Builder\User', $users);
        assertType('int', $page);
    });

    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', Post::query());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', Post::on());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', Post::onWriteConnection());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', Post::with([]));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', $post->newQuery());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', $post->newEloquentBuilder($queryBuilder));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', $post->newModelQuery());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', $post->newQueryWithoutRelationships());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', $post->newQueryWithoutScopes());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', $post->newQueryWithoutScope('foo'));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', $post->newQueryForRestoration(1));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', $post->newQuery()->where('foo', 'bar'));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\Post>', $post->newQuery()->foo());
    assertType('Heritage\Types\Builder\Post', $post->newQuery()->create(['name' => 'John']));

    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', ChildPost::query());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', ChildPost::on());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', ChildPost::onWriteConnection());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', ChildPost::with([]));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', $childPost->newQuery());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', $childPost->newEloquentBuilder($queryBuilder));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', $childPost->newModelQuery());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', $childPost->newQueryWithoutRelationships());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', $childPost->newQueryWithoutScopes());
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', $childPost->newQueryWithoutScope('foo'));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', $childPost->newQueryForRestoration(1));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', $childPost->newQuery()->where('foo', 'bar'));
    assertType('Heritage\Types\Builder\CommonBuilder<Heritage\Types\Builder\ChildPost>', $childPost->newQuery()->foo());
    assertType('Heritage\Types\Builder\ChildPost', $childPost->newQuery()->create(['name' => 'John']));

    assertType('Heritage\Types\Builder\CommentBuilder', Comment::query());
    assertType('Heritage\Types\Builder\CommentBuilder', Comment::on());
    assertType('Heritage\Types\Builder\CommentBuilder', Comment::onWriteConnection());
    assertType('Heritage\Types\Builder\CommentBuilder', Comment::with([]));
    assertType('Heritage\Types\Builder\CommentBuilder', $comment->newQuery());
    assertType('Heritage\Types\Builder\CommentBuilder', $comment->newEloquentBuilder($queryBuilder));
    assertType('Heritage\Types\Builder\CommentBuilder', $comment->newModelQuery());
    assertType('Heritage\Types\Builder\CommentBuilder', $comment->newQueryWithoutRelationships());
    assertType('Heritage\Types\Builder\CommentBuilder', $comment->newQueryWithoutScopes());
    assertType('Heritage\Types\Builder\CommentBuilder', $comment->newQueryWithoutScope('foo'));
    assertType('Heritage\Types\Builder\CommentBuilder', $comment->newQueryForRestoration(1));
    assertType('Heritage\Types\Builder\CommentBuilder', $comment->newQuery()->where('foo', 'bar'));
    assertType('Heritage\Types\Builder\CommentBuilder', $comment->newQuery()->foo());
    assertType('Heritage\Types\Builder\Comment', $comment->newQuery()->create(['name' => 'John']));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->pipe(function () {
        //
    }));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->pipe(fn () => null));
    assertType('Heritage\Database\Eloquent\Builder<Heritage\Types\Builder\User>', $query->pipe(fn ($query) => $query));
    assertType('5', $query->pipe(fn ($query) => 5));
}

class User extends Model
{
    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model
{
    /** @use HasBuilder<CommonBuilder<static>> */
    use HasBuilder;

    protected static string $builder = CommonBuilder::class;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<\Heritage\Database\Eloquent\Model, $this> */
    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}

class ChildPost extends Post
{
}

class Comment extends Model
{
    /** @use HasBuilder<CommentBuilder> */
    use HasBuilder;

    protected static string $builder = CommentBuilder::class;
}

/**
 * @template TModel of \Heritage\Database\Eloquent\Model
 *
 * @extends \Heritage\Database\Eloquent\Builder<TModel>
 */
class CommonBuilder extends Builder
{
    /** @return $this */
    public function foo(): static
    {
        return $this->where('foo', 'bar');
    }
}

/** @extends CommonBuilder<Comment> */
class CommentBuilder extends CommonBuilder
{
}
