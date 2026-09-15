<?php

namespace Heritage\Tests\Integration\Database\Fixtures;

use Heritage\Database\Eloquent\Model;

class PostStringyKey extends Model
{
    public $table = 'my_posts';

    public $primaryKey = 'my_id';
}
