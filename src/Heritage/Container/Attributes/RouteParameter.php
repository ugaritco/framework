<?php

namespace Heritage\Container\Attributes;

use Attribute;
use Heritage\Contracts\Container\Container;
use Heritage\Contracts\Container\ContextualAttribute;
use ReflectionParameter;

#[Attribute(Attribute::TARGET_PARAMETER)]
class RouteParameter implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public ?string $parameter = null)
    {
    }

    /**
     * Resolve the route parameter.
     *
     * @param  self  $attribute
     * @param  \Heritage\Contracts\Container\Container  $container
     * @return mixed
     */
    public static function resolve(self $attribute, Container $container, ReflectionParameter $parameter)
    {
        return $container->make('request')->route($attribute->parameter ?? $parameter->getName());
    }
}
