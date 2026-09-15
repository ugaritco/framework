<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Database\Eloquent\Attributes\UseFactory;
use Heritage\Database\Eloquent\Attributes\UseResource;
use Heritage\Database\Eloquent\Factories\HasFactory;
use Heritage\Foundation\Auth\User as Authenticatable;
use Orchestra\Testbench\Factories\UserFactory;

#[UseResource(UserResource::class)]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    use HasFactory;

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function chaperonePosts()
    {
        return $this->hasMany(Post::class)->chaperone('author');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('role')
            ->withTimestamps()
            ->using(Membership::class)
            ->as('membership');
    }
}
