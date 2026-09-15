<?php

namespace Heritage\Contracts\Database\Eloquent;

use Heritage\Database\Eloquent\Model;

interface ComparesCastableAttributes
{
    /**
     * Determine if the given values are equal.
     *
     * @param  \Heritage\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $firstValue
     * @param  mixed  $secondValue
     * @return bool
     */
    public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue);
}
