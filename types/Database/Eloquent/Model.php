<?php

namespace Heritage\Types\Model;

use Heritage\Database\Eloquent\Attributes\CollectedBy;
use Heritage\Database\Eloquent\Collection;
use Heritage\Database\Eloquent\HasCollection;
use Heritage\Database\Eloquent\Model;
use Heritage\Support\Carbon;
use User;

use function PHPStan\Testing\assertType;

function test(User $user, Post $post, Comment $comment, Article $article): void
{
    assertType('UserFactory', User::factory(function ($attributes, $model) {
        assertType('array<string, mixed>', $attributes);
        assertType('User|null', $model);

        return ['string' => 'string'];
    }));
    assertType('UserFactory', User::factory(42, function ($attributes, $model) {
        assertType('array<string, mixed>', $attributes);
        assertType('User|null', $model);

        return ['string' => 'string'];
    }));

    User::addGlobalScope('ancient', function ($builder) {
        assertType('Heritage\Database\Eloquent\Builder<User>', $builder);

        $builder->where('created_at', '<', Carbon::now()->subYears(2000));
    });

    assertType('Heritage\Database\Eloquent\Builder<User>', User::query());
    assertType('Heritage\Database\Eloquent\Builder<User>', $user->newQuery());
    assertType('Heritage\Database\Eloquent\Builder<User>', $user->withTrashed());
    assertType('Heritage\Database\Eloquent\Builder<User>', $user->onlyTrashed());
    assertType('Heritage\Database\Eloquent\Builder<User>', $user->withoutTrashed());
    assertType('Heritage\Database\Eloquent\Builder<User>', $user->prunable());
    assertType('Heritage\Database\Eloquent\Relations\MorphMany<Heritage\Notifications\DatabaseNotification, User>', $user->notifications());
    assertType('Heritage\Database\Eloquent\Relations\MorphMany<Heritage\Notifications\DatabaseNotification, User>', $user->unreadNotifications());

    assertType('Heritage\Database\Eloquent\Collection<(int|string), User>', $user->newCollection([new User()]));
    assertType('Heritage\Types\Model\Posts<(int|string), Heritage\Types\Model\Post>', $post->newCollection(['foo' => new Post()]));
    assertType('Heritage\Types\Model\Articles<(int|string), Heritage\Types\Model\Article>', $article->newCollection([new Article()]));
    assertType('Heritage\Types\Model\Comments', $comment->newCollection([new Comment()]));

    assertType('bool', $user->restore());
    assertType('User', $user->restoreOrCreate());
    assertType('User', $user->createOrRestore());

    assertType("'foo'", User::withoutEvents(fn () => 'foo'));
    assertType("'foo'", User::withoutBroadcasting(fn () => 'foo'));
    assertType("'foo'", User::withoutTimestampsOn([], fn () => 'foo'));
    assertType("'foo'", User::withoutTimestamps(fn () => 'foo'));
}

class Post extends Model
{
    /** @use HasCollection<Posts<array-key, static>> */
    use HasCollection;

    protected static string $collectionClass = Posts::class;
}

/**
 * @template TKey of array-key
 * @template TModel of Post
 *
 * @extends Collection<TKey, TModel> */
class Posts extends Collection
{
}

final class Comment extends Model
{
    /** @use HasCollection<Comments> */
    use HasCollection;

    /** @param  array<array-key, Comment>  $models */
    public function newCollection(array $models = []): Comments
    {
        return new Comments($models);
    }
}

/** @extends Collection<array-key, Comment> */
final class Comments extends Collection
{
}

#[CollectedBy(Articles::class)]
class Article extends Model
{
    /** @use HasCollection<Articles<array-key, static>> */
    use HasCollection;
}

/**
 * @template TKey of array-key
 * @template TModel of Article
 *
 * @extends Collection<TKey, TModel> */
class Articles extends Collection
{
}
