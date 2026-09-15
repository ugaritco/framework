<?php

namespace Heritage\Tests\Database\Fixtures\Models\PostLikes;

use Heritage\Database\Eloquent\Model;

class Like extends Model
{
    public $timestamps = false;

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
