<?php

namespace Heritage\Database\Eloquent;

/**
 * @template TModel of \Heritage\Database\Eloquent\Model
 */
interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Heritage\Database\Eloquent\Builder<covariant TModel>  $builder
     * @param  TModel  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
