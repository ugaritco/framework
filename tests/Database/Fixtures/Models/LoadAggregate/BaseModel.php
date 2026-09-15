<?php

namespace Heritage\Tests\Database\Fixtures\Models\LoadAggregate;

use Heritage\Database\Eloquent\Model;

class BaseModel extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function related1()
    {
        return $this->hasMany(Related1::class);
    }

    public function related2()
    {
        return $this->hasMany(Related2::class);
    }
}
