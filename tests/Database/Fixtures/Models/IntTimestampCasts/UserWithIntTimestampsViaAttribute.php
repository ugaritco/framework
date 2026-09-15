<?php

namespace Heritage\Tests\Database\Fixtures\Models\IntTimestampCasts;

use Heritage\Database\Eloquent\Casts\Attribute;
use Heritage\Database\Eloquent\Model;
use Heritage\Support\Carbon;

class UserWithIntTimestampsViaAttribute extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value),
            set: fn ($value) => Carbon::parse($value)->getTimestamp(),
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value),
            set: fn ($value) => Carbon::parse($value)->getTimestamp(),
        );
    }
}
