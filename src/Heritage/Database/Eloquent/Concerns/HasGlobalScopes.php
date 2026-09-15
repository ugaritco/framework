<?php

namespace Heritage\Database\Eloquent\Concerns;

use Closure;
use Heritage\Database\Eloquent\Attributes\ScopedBy;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Scope;
use Heritage\Support\Arr;
use Heritage\Support\Collection;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;

trait HasGlobalScopes
{
    /**
     * Boot the has global scopes trait for a model.
     *
     * @return void
     */
    public static function bootHasGlobalScopes()
    {
        static::addGlobalScopes(static::resolveGlobalScopeAttributes());
    }

    /**
     * Resolve the global scope class names from the attributes.
     *
     * @return array
     */
    public static function resolveGlobalScopeAttributes()
    {
        $reflectionClass = new ReflectionClass(static::class);

        $attributes = (new Collection($reflectionClass->getAttributes(ScopedBy::class, ReflectionAttribute::IS_INSTANCEOF)));

        foreach ($reflectionClass->getTraits() as $trait) {
            $attributes->push(...$trait->getAttributes(ScopedBy::class, ReflectionAttribute::IS_INSTANCEOF));
        }

        $isEloquentGrandchild = is_subclass_of(static::class, Model::class)
            && get_parent_class(static::class) !== Model::class;

        return $attributes->map(fn ($attribute) => $attribute->getArguments())
            ->flatten()
            ->when($isEloquentGrandchild, function (Collection $attributes) {
                return (new Collection(get_parent_class(static::class)::resolveGlobalScopeAttributes()))
                    ->merge($attributes);
            })
            ->all();
    }

    /**
     * Register a new global scope on the model.
     *
     * @param  \Heritage\Database\Eloquent\Scope|(\Closure(\Heritage\Database\Eloquent\Builder<static>): mixed)|string  $scope
     * @param  \Heritage\Database\Eloquent\Scope|(\Closure(\Heritage\Database\Eloquent\Builder<static>): mixed)|null  $implementation
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function addGlobalScope($scope, $implementation = null)
    {
        if (is_string($scope) && ($implementation instanceof Closure || $implementation instanceof Scope)) {
            return static::$globalScopes[static::class][$scope] = $implementation;
        } elseif ($scope instanceof Closure) {
            return static::$globalScopes[static::class][spl_object_id($scope)] = $scope;
        } elseif ($scope instanceof Scope) {
            return static::$globalScopes[static::class][get_class($scope)] = $scope;
        } elseif (is_string($scope) && class_exists($scope) && is_subclass_of($scope, Scope::class)) {
            return static::$globalScopes[static::class][$scope] = new $scope;
        }

        throw new InvalidArgumentException('Global scope must be an instance of Closure or Scope or be a class name of a class extending '.Scope::class);
    }

    /**
     * Register multiple global scopes on the model.
     *
     * @param  array  $scopes
     * @return void
     */
    public static function addGlobalScopes(array $scopes)
    {
        foreach ($scopes as $key => $scope) {
            if (is_string($key)) {
                static::addGlobalScope($key, $scope);
            } else {
                static::addGlobalScope($scope);
            }
        }
    }

    /**
     * Determine if a model has a global scope.
     *
     * @param  \Heritage\Database\Eloquent\Scope|string  $scope
     * @return bool
     */
    public static function hasGlobalScope($scope)
    {
        return ! is_null(static::getGlobalScope($scope));
    }

    /**
     * Get a global scope registered with the model.
     *
     * @param  \Heritage\Database\Eloquent\Scope|string  $scope
     * @return \Heritage\Database\Eloquent\Scope|(\Closure(\Heritage\Database\Eloquent\Builder<static>): mixed)|null
     */
    public static function getGlobalScope($scope)
    {
        if (is_string($scope)) {
            return Arr::get(static::$globalScopes, static::class.'.'.$scope);
        }

        return Arr::get(
            static::$globalScopes, static::class.'.'.get_class($scope)
        );
    }

    /**
     * Get all of the global scopes that are currently registered.
     *
     * @return array
     */
    public static function getAllGlobalScopes()
    {
        return static::$globalScopes;
    }

    /**
     * Set the current global scopes.
     *
     * @param  array  $scopes
     * @return void
     */
    public static function setAllGlobalScopes($scopes)
    {
        static::$globalScopes = $scopes;
    }

    /**
     * Get the global scopes for this class instance.
     *
     * @return array
     */
    public function getGlobalScopes()
    {
        return Arr::get(static::$globalScopes, static::class, []);
    }
}
