<?php

namespace Heritage\Tests\Database\Fixtures\Models\LoadAggregate;

use Heritage\Database\Eloquent\Model;

class Related2 extends Model
{
    public $timestamps = false;

    protected $fillable = ['base_model_id', 'number'];

    public function parent()
    {
        return $this->belongsTo(BaseModel::class);
    }
}
