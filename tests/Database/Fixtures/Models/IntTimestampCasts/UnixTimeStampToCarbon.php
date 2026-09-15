<?php

namespace Heritage\Tests\Database\Fixtures\Models\IntTimestampCasts;

use Heritage\Contracts\Database\Eloquent\CastsAttributes;
use Heritage\Support\Carbon;

class UnixTimeStampToCarbon implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return Carbon::parse($value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return Carbon::parse($value)->getTimestamp();
    }
}
