<?php

namespace Heritage\Tests\Database\Fixtures\Models\IntTimestampCasts;

use Heritage\Database\Eloquent\Model;
use Heritage\Support\Carbon;

class UserWithIntTimestampsViaMutator extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    protected function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value);
    }

    protected function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->getTimestamp();
    }

    protected function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value);
    }

    protected function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->getTimestamp();
    }
}
