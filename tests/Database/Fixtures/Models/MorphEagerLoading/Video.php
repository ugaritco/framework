<?php

namespace Heritage\Tests\Database\Fixtures\Models\MorphEagerLoading;

use Heritage\Database\Eloquent\Model;

class Video extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'video_id';
}
