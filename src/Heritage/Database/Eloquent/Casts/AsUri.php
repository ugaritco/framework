<?php

namespace Heritage\Database\Eloquent\Casts;

use Heritage\Contracts\Database\Eloquent\Castable;
use Heritage\Contracts\Database\Eloquent\CastsAttributes;
use Heritage\Support\Uri;

class AsUri implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Heritage\Contracts\Database\Eloquent\CastsAttributes<\Heritage\Support\Uri, string|Uri>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? new Uri($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $value : null;
            }
        };
    }
}
