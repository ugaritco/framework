<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Database\Eloquent\Attributes\UseFactory;
use Heritage\Database\Eloquent\Attributes\UseResource;
use Heritage\Database\Eloquent\Factories\HasFactory;
use Heritage\Database\Eloquent\Model;

#[UseFactory(CommentFactory::class)]
#[UseResource(CommentResource::class)]
class Comment extends Model
{
    use HasFactory;

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function commenter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
