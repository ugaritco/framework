<?php

namespace Heritage\Container\Attributes;

use Attribute;
use Heritage\Contracts\Container\Container;
use Heritage\Contracts\Container\ContextualAttribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Storage implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public UnitEnum|string|null $disk = null)
    {
    }

    /**
     * Resolve the storage disk.
     *
     * @param  self  $attribute
     * @param  \Heritage\Contracts\Container\Container  $container
     * @return \Heritage\Contracts\Filesystem\Filesystem
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->make('filesystem')->disk($attribute->disk);
    }
}
