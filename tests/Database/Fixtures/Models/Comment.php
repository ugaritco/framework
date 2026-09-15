<?php

namespace Heritage\Tests\Database\Fixtures\Models;

use Heritage\Database\Eloquent\Model;

class Comment extends Model
{
    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }
}
