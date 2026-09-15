<?php

namespace Heritage\Tests\Database\Fixtures\Models\IntTimestampCasts;

use Heritage\Database\Eloquent\Model;

class UserWithIntTimestampsViaCasts extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    protected $casts = [
        'created_at' => UnixTimeStampToCarbon::class,
        'updated_at' => UnixTimeStampToCarbon::class,
    ];
}
