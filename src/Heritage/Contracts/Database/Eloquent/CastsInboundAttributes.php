<?php

namespace Heritage\Contracts\Database\Eloquent;

use Heritage\Database\Eloquent\Model;

interface CastsInboundAttributes
{
    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  \Heritage\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes);
}
