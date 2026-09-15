<?php

namespace Heritage\Types\Scope;

use Heritage\Database\Eloquent\Builder;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Scope;

use function PHPStan\Testing\assertType;

/**
 * @implements Scope<User>
 */
class UserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        assertType('Heritage\Database\Eloquent\Builder<covariant Heritage\Types\Scope\User>', $builder);
        assertType('Heritage\Types\Scope\User', $model);
    }
}

/**
 * @implements Scope<Model>
 */
class GenericScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        assertType('Heritage\Database\Eloquent\Builder<covariant Heritage\Database\Eloquent\Model>', $builder);
        assertType('Heritage\Database\Eloquent\Model', $model);
    }
}

class User extends Model
{
}

$user = new User();
$query = User::query();
(new UserScope())->apply($query, $user);
(new GenericScope())->apply($query, $user);
