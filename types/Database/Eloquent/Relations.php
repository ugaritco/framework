<?php

namespace Heritage\Types\Relations;

use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Relations\BelongsTo;
use Heritage\Database\Eloquent\Relations\BelongsToMany;
use Heritage\Database\Eloquent\Relations\HasMany;
use Heritage\Database\Eloquent\Relations\HasManyThrough;
use Heritage\Database\Eloquent\Relations\HasOne;
use Heritage\Database\Eloquent\Relations\HasOneThrough;
use Heritage\Database\Eloquent\Relations\MorphMany;
use Heritage\Database\Eloquent\Relations\MorphOne;
use Heritage\Database\Eloquent\Relations\MorphTo;
use Heritage\Database\Eloquent\Relations\MorphToMany;
use Heritage\Database\Eloquent\Relations\Pivot;
use Heritage\Database\Eloquent\Relations\Relation;

use function PHPStan\Testing\assertType;

function test(User $user, Post $post, Comment $comment, ChildUser $child): void
{
    assertType('Heritage\Database\Eloquent\Relations\HasOne<Heritage\Types\Relations\Address, Heritage\Types\Relations\User>', $user->address());
    assertType('Heritage\Types\Relations\Address|null', $user->address()->getResults());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Address>', $user->address()->get());
    assertType('Heritage\Types\Relations\Address', $user->address()->make());
    assertType('Heritage\Types\Relations\Address', $user->address()->create());
    assertType('Heritage\Database\Eloquent\Relations\HasOne<Heritage\Types\Relations\Address, Heritage\Types\Relations\ChildUser>', $child->address());
    assertType('Heritage\Types\Relations\Address', $child->address()->make());
    assertType('Heritage\Types\Relations\Address', $child->address()->create([]));
    assertType('Heritage\Types\Relations\Address', $child->address()->getRelated());
    assertType('Heritage\Types\Relations\ChildUser', $child->address()->getParent());

    assertType('Heritage\Database\Eloquent\Relations\HasMany<Heritage\Types\Relations\Post, Heritage\Types\Relations\User>', $user->posts());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Post>', $user->posts()->getResults());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Post>', $user->posts()->makeMany([]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Post>', $user->posts()->createMany([]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Post>', $user->posts()->createManyQuietly([]));
    assertType('Heritage\Database\Eloquent\Relations\HasOne<Heritage\Types\Relations\Post, Heritage\Types\Relations\User>', $user->latestPost());
    assertType('Heritage\Types\Relations\Post', $user->posts()->make());
    assertType('Heritage\Types\Relations\Post', $user->posts()->create());
    assertType('Heritage\Types\Relations\Post|false', $user->posts()->save(new Post()));
    assertType('Heritage\Types\Relations\Post|false', $user->posts()->saveQuietly(new Post()));

    assertType("Heritage\Database\Eloquent\Relations\BelongsToMany<Heritage\Types\Relations\Role, Heritage\Types\Relations\User, Heritage\Database\Eloquent\Relations\Pivot, 'pivot'>", $user->roles());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->getResults());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->find([1]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->findMany([1, 2, 3]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->findOrNew([1]));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->findOrFail([1]));
    assertType('42|Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->findOr([1], fn () => 42));
    assertType('42|Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->findOr([1], callback: fn () => 42));
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->findOrNew(1));
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->findOrFail(1));
    assertType('(Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot})|null', $user->roles()->find(1));
    assertType('42|(Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot})', $user->roles()->findOr(1, fn () => 42));
    assertType('42|(Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot})', $user->roles()->findOr(1, callback: fn () => 42));
    assertType('(Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot})|null', $user->roles()->first());
    assertType('42|(Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot})', $user->roles()->firstOr(fn () => 42));
    assertType('42|(Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot})', $user->roles()->firstOr(callback: fn () => 42));
    assertType('(Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot})|null', $user->roles()->firstWhere('foo'));
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->firstOrNew());
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->firstOrFail());
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->firstOrCreate());
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->create());
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->createOrFirst());
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->updateOrCreate([]));
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->save(new Role()));
    assertType('Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}', $user->roles()->saveQuietly(new Role()));
    $roles = $user->roles()->getResults();
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->saveMany($roles));
    assertType('array<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->saveMany($roles->all()));
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->saveManyQuietly($roles));
    assertType('array<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->saveManyQuietly($roles->all()));
    assertType('array<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->createMany($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->sync($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->syncWithoutDetaching($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->syncWithPivotValues($roles, []));
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->lazy());
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->lazyById());
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Relations\Role&object{pivot: Heritage\Database\Eloquent\Relations\Pivot}>', $user->roles()->cursor());

    assertType('Heritage\Database\Eloquent\Relations\HasOneThrough<Heritage\Types\Relations\Car, Heritage\Types\Relations\Mechanic, Heritage\Types\Relations\User>', $user->car());
    assertType('Heritage\Types\Relations\Car|null', $user->car()->getResults());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Car>', $user->car()->find([1]));
    assertType('42|Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Car>', $user->car()->findOr([1], fn () => 42));
    assertType('42|Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Car>', $user->car()->findOr([1], callback: fn () => 42));
    assertType('Heritage\Types\Relations\Car|null', $user->car()->find(1));
    assertType('42|Heritage\Types\Relations\Car', $user->car()->findOr(1, fn () => 42));
    assertType('42|Heritage\Types\Relations\Car', $user->car()->findOr(1, callback: fn () => 42));
    assertType('Heritage\Types\Relations\Car|null', $user->car()->first());
    assertType('42|Heritage\Types\Relations\Car', $user->car()->firstOr(fn () => 42));
    assertType('42|Heritage\Types\Relations\Car', $user->car()->firstOr(callback: fn () => 42));
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Relations\Car>', $user->car()->lazy());
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Relations\Car>', $user->car()->lazyById());
    assertType('Heritage\Support\LazyCollection<int, Heritage\Types\Relations\Car>', $user->car()->cursor());

    assertType('Heritage\Database\Eloquent\Relations\HasManyThrough<Heritage\Types\Relations\Part, Heritage\Types\Relations\Mechanic, Heritage\Types\Relations\User>', $user->parts());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Part>', $user->parts()->getResults());
    assertType('Heritage\Database\Eloquent\Relations\HasOneThrough<Heritage\Types\Relations\Part, Heritage\Types\Relations\Mechanic, Heritage\Types\Relations\User>', $user->firstPart());

    assertType('Heritage\Database\Eloquent\Relations\BelongsTo<Heritage\Types\Relations\User, Heritage\Types\Relations\Post>', $post->user());
    assertType('Heritage\Types\Relations\User|null', $post->user()->getResults());
    assertType('Heritage\Types\Relations\User', $post->user()->make());
    assertType('Heritage\Types\Relations\User', $post->user()->create());
    assertType('Heritage\Types\Relations\Post', $post->user()->associate(new User()));
    assertType('Heritage\Types\Relations\Post', $post->user()->dissociate());
    assertType('Heritage\Types\Relations\Post', $post->user()->disassociate());
    assertType('Heritage\Types\Relations\Post', $post->user()->getChild());

    assertType('Heritage\Database\Eloquent\Relations\MorphOne<Heritage\Types\Relations\Image, Heritage\Types\Relations\Post>', $post->image());
    assertType('Heritage\Types\Relations\Image|null', $post->image()->getResults());
    assertType('Heritage\Types\Relations\Image', $post->image()->forceCreate([]));

    assertType('Heritage\Database\Eloquent\Relations\MorphMany<Heritage\Types\Relations\Comment, Heritage\Types\Relations\Post>', $post->comments());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Comment>', $post->comments()->getResults());
    assertType('Heritage\Database\Eloquent\Relations\MorphOne<Heritage\Types\Relations\Comment, Heritage\Types\Relations\Post>', $post->latestComment());

    assertType('Heritage\Database\Eloquent\Relations\MorphTo<Heritage\Database\Eloquent\Model, Heritage\Types\Relations\Comment>', $comment->commentable());
    assertType('Heritage\Database\Eloquent\Model|null', $comment->commentable()->getResults());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Comment>', $comment->commentable()->getEager());
    assertType('Heritage\Database\Eloquent\Model', $comment->commentable()->createModelByType('foo'));
    assertType('Heritage\Types\Relations\Comment', $comment->commentable()->associate(new Post()));
    assertType('Heritage\Types\Relations\Comment', $comment->commentable()->dissociate());

    assertType("Heritage\Database\Eloquent\Relations\MorphToMany<Heritage\Types\Relations\Tag, Heritage\Types\Relations\Post, Heritage\Database\Eloquent\Relations\MorphPivot, 'pivot'>", $post->tags());
    assertType('Heritage\Database\Eloquent\Collection<int, Heritage\Types\Relations\Tag&object{pivot: Heritage\Database\Eloquent\Relations\MorphPivot}>', $post->tags()->getResults());

    assertType('42', Relation::noConstraints(fn () => 42));
}

class User extends Model
{
    /** @return HasOne<Address, $this> */
    public function address(): HasOne
    {
        $hasOne = $this->hasOne(Address::class);
        assertType('Heritage\Database\Eloquent\Relations\HasOne<Heritage\Types\Relations\Address, $this(Heritage\Types\Relations\User)>', $hasOne);

        return $hasOne;
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        $hasMany = $this->hasMany(Post::class);
        assertType('Heritage\Database\Eloquent\Relations\HasMany<Heritage\Types\Relations\Post, $this(Heritage\Types\Relations\User)>', $hasMany);

        return $hasMany;
    }

    /** @return HasOne<Post, $this> */
    public function latestPost(): HasOne
    {
        $post = $this->posts()->one();
        assertType('Heritage\Database\Eloquent\Relations\HasOne<Heritage\Types\Relations\Post, $this(Heritage\Types\Relations\User)>', $post);

        return $post;
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        $belongsToMany = $this->belongsToMany(Role::class);
        assertType('Heritage\Database\Eloquent\Relations\BelongsToMany<Heritage\Types\Relations\Role, $this(Heritage\Types\Relations\User), Heritage\Database\Eloquent\Relations\Pivot, \'pivot\'>', $belongsToMany);

        return $belongsToMany;
    }

    /** @return BelongsToMany<Role, $this, Tenant> */
    public function tenantRoles(): BelongsToMany
    {
        $belongsToMany = $this->belongsToMany(Role::class)->using(Tenant::class);
        assertType('Heritage\Database\Eloquent\Relations\BelongsToMany<Heritage\Types\Relations\Role, $this(Heritage\Types\Relations\User), Heritage\Types\Relations\Tenant, \'pivot\'>', $belongsToMany);

        $belongsToManyShorthand = $this->belongsToMany(Role::class, Tenant::class);
        assertType('Heritage\Database\Eloquent\Relations\BelongsToMany<Heritage\Types\Relations\Role, $this(Heritage\Types\Relations\User), Heritage\Types\Relations\Tenant, \'pivot\'>', $belongsToManyShorthand);

        return $belongsToMany;
    }

    /** @return HasOne<Mechanic, $this> */
    public function mechanic(): HasOne
    {
        return $this->hasOne(Mechanic::class);
    }

    /** @return HasMany<Mechanic, $this> */
    public function mechanics(): HasMany
    {
        return $this->hasMany(Mechanic::class);
    }

    /** @return HasOneThrough<Car, Mechanic, $this> */
    public function car(): HasOneThrough
    {
        $hasOneThrough = $this->hasOneThrough(Car::class, Mechanic::class);
        assertType('Heritage\Database\Eloquent\Relations\HasOneThrough<Heritage\Types\Relations\Car, Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User)>', $hasOneThrough);

        $through = $this->through('mechanic');
        assertType(
            'Heritage\Database\Eloquent\PendingHasThroughRelationship<Heritage\Database\Eloquent\Model, $this(Heritage\Types\Relations\User)>',
            $through,
        );
        assertType(
            'Heritage\Database\Eloquent\Relations\HasManyThrough<Heritage\Database\Eloquent\Model, Heritage\Database\Eloquent\Model, $this(Heritage\Types\Relations\User)>|Heritage\Database\Eloquent\Relations\HasOneThrough<Heritage\Database\Eloquent\Model, Heritage\Database\Eloquent\Model, $this(Heritage\Types\Relations\User)>',
            $through->has('car'),
        );

        $through = $this->through($this->mechanic());
        assertType(
            'Heritage\Database\Eloquent\PendingHasThroughRelationship<Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User), Heritage\Database\Eloquent\Relations\HasOne<Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User)>>',
            $through,
        );
        assertType(
            'Heritage\Database\Eloquent\Relations\HasOneThrough<Heritage\Types\Relations\Car, Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User)>',
            $through->has(function ($mechanic) {
                assertType('Heritage\Types\Relations\Mechanic', $mechanic);

                return $mechanic->car();
            }),
        );

        return $hasOneThrough;
    }

    /** @return HasManyThrough<Car, Mechanic, $this> */
    public function cars(): HasManyThrough
    {
        $through = $this->through($this->mechanics());
        assertType(
            'Heritage\Database\Eloquent\PendingHasThroughRelationship<Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User), Heritage\Database\Eloquent\Relations\HasMany<Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User)>>',
            $through,
        );
        $hasManyThrough = $through->has(function ($mechanic) {
            assertType('Heritage\Types\Relations\Mechanic', $mechanic);

            return $mechanic->car();
        });
        assertType(
            'Heritage\Database\Eloquent\Relations\HasManyThrough<Heritage\Types\Relations\Car, Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User)>',
            $hasManyThrough,
        );

        return $hasManyThrough;
    }

    /** @return HasManyThrough<Part, Mechanic, $this> */
    public function parts(): HasManyThrough
    {
        $hasManyThrough = $this->hasManyThrough(Part::class, Mechanic::class);
        assertType('Heritage\Database\Eloquent\Relations\HasManyThrough<Heritage\Types\Relations\Part, Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User)>', $hasManyThrough);

        assertType(
            'Heritage\Database\Eloquent\Relations\HasManyThrough<Heritage\Types\Relations\Part, Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User)>',
            $this->through($this->mechanic())->has(fn ($mechanic) => $mechanic->parts()),
        );

        return $hasManyThrough;
    }

    /** @return HasOneThrough<Part, Mechanic, $this> */
    public function firstPart(): HasOneThrough
    {
        $part = $this->parts()->one();
        assertType('Heritage\Database\Eloquent\Relations\HasOneThrough<Heritage\Types\Relations\Part, Heritage\Types\Relations\Mechanic, $this(Heritage\Types\Relations\User)>', $part);

        return $part;
    }
}

class Post extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        $belongsTo = $this->belongsTo(User::class);
        assertType('Heritage\Database\Eloquent\Relations\BelongsTo<Heritage\Types\Relations\User, $this(Heritage\Types\Relations\Post)>', $belongsTo);

        return $belongsTo;
    }

    /** @return MorphOne<Image, $this> */
    public function image(): MorphOne
    {
        $morphOne = $this->morphOne(Image::class, 'imageable');
        assertType('Heritage\Database\Eloquent\Relations\MorphOne<Heritage\Types\Relations\Image, $this(Heritage\Types\Relations\Post)>', $morphOne);

        return $morphOne;
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        $morphMany = $this->morphMany(Comment::class, 'commentable');
        assertType('Heritage\Database\Eloquent\Relations\MorphMany<Heritage\Types\Relations\Comment, $this(Heritage\Types\Relations\Post)>', $morphMany);

        return $morphMany;
    }

    /** @return MorphOne<Comment, $this> */
    public function latestComment(): MorphOne
    {
        $comment = $this->comments()->one();
        assertType('Heritage\Database\Eloquent\Relations\MorphOne<Heritage\Types\Relations\Comment, $this(Heritage\Types\Relations\Post)>', $comment);

        return $comment;
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        $morphToMany = $this->morphedByMany(Tag::class, 'taggable');
        assertType('Heritage\Database\Eloquent\Relations\MorphToMany<Heritage\Types\Relations\Tag, $this(Heritage\Types\Relations\Post), Heritage\Database\Eloquent\Relations\MorphPivot, \'pivot\'>', $morphToMany);

        return $morphToMany;
    }
}

class Comment extends Model
{
    /** @return MorphTo<\Heritage\Database\Eloquent\Model, $this> */
    public function commentable(): MorphTo
    {
        $morphTo = $this->morphTo();
        assertType('Heritage\Database\Eloquent\Relations\MorphTo<Heritage\Database\Eloquent\Model, $this(Heritage\Types\Relations\Comment)>', $morphTo);

        return $morphTo;
    }
}

class Tag extends Model
{
    /** @return MorphToMany<Post, $this> */
    public function posts(): MorphToMany
    {
        $morphToMany = $this->morphToMany(Post::class, 'taggable');
        assertType('Heritage\Database\Eloquent\Relations\MorphToMany<Heritage\Types\Relations\Post, $this(Heritage\Types\Relations\Tag), Heritage\Database\Eloquent\Relations\MorphPivot, \'pivot\'>', $morphToMany);

        return $morphToMany;
    }
}

class Mechanic extends Model
{
    /** @return HasOne<Car, $this> */
    public function car(): HasOne
    {
        return $this->hasOne(Car::class);
    }

    /** @return HasMany<Part, $this> */
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}

class ChildUser extends User
{
}
class Address extends Model
{
}
class Role extends Model
{
}
class Tenant extends Pivot
{
}
class Car extends Model
{
}
class Part extends Model
{
}
class Image extends Model
{
}
