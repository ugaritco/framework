<?php

namespace Heritage\Tests\Database\Fixtures\Models\IntTimestampCasts;

use Heritage\Database\Eloquent\Model;

class UserWithUpdatedAtViaMutator extends Model
{
    protected $table = 'users_nullable_timestamps';

    protected $fillable = ['email', 'updated_at'];

    public function setUpdatedAtAttribute($value)
    {
        if (! $this->id) {
            return;
        }

        $this->updated_at = $value;
    }
}
