<?php

namespace Heritage\Tests\Database\Fixtures\Models\PostLikes;

use Heritage\Database\Eloquent\Model;

class Post extends Model
{
    public $timestamps = false;

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
