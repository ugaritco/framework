<?php

namespace Heritage\Contracts\Database\Eloquent;

use Heritage\Database\Eloquent\Model;

interface SerializesCastableAttributes
{
    /**
     * Serialize the attribute when converting the model to an array.
     *
     * @param  \Heritage\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return mixed
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes);
}
