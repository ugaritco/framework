<?php

namespace Heritage\Database\Eloquent\Casts;

use Heritage\Contracts\Database\Eloquent\Castable;
use Heritage\Contracts\Database\Eloquent\CastsAttributes;
use Heritage\Support\Stringable;

class AsStringable implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Heritage\Contracts\Database\Eloquent\CastsAttributes<\Heritage\Support\Stringable, string|\Stringable>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? new Stringable($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $value : null;
            }
        };
    }
}
