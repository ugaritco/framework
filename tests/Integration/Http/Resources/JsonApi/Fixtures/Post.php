<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Database\Eloquent\Attributes\UseFactory;
use Heritage\Database\Eloquent\Attributes\UseResource;
use Heritage\Database\Eloquent\Factories\HasFactory;
use Heritage\Database\Eloquent\Model;

#[UseFactory(PostFactory::class)]
#[UseResource(PostResource::class)]
class Post extends Model
{
    use HasFactory;

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
