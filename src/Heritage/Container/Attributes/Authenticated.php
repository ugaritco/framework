<?php

namespace Heritage\Container\Attributes;

use Attribute;
use Heritage\Contracts\Container\Container;
use Heritage\Contracts\Container\ContextualAttribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Authenticated implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public UnitEnum|string|null $guard = null)
    {
    }

    /**
     * Resolve the currently authenticated user.
     *
     * @param  self  $attribute
     * @param  \Heritage\Contracts\Container\Container  $container
     * @return \Heritage\Contracts\Auth\Authenticatable|null
     */
    public static function resolve(self $attribute, Container $container)
    {
        return call_user_func($container->make('auth')->userResolver(), $attribute->guard);
    }
}
