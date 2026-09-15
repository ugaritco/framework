<?php

namespace Heritage\Container\Attributes;

use Attribute;
use Heritage\Contracts\Container\Container;
use Heritage\Contracts\Container\ContextualAttribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Auth implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public UnitEnum|string|null $guard = null)
    {
    }

    /**
     * Resolve the authentication guard.
     *
     * @param  self  $attribute
     * @param  \Heritage\Contracts\Container\Container  $container
     * @return \Heritage\Contracts\Auth\Guard|\Heritage\Contracts\Auth\StatefulGuard
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->make('auth')->guard($attribute->guard);
    }
}
