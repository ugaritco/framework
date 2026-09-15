<?php

namespace Heritage\Container\Attributes;

use Attribute;
use Heritage\Contracts\Container\Container;
use Heritage\Contracts\Container\ContextualAttribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Cache implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public UnitEnum|string|null $store = null,
        public bool $memo = false,
    ) {
    }

    /**
     * Resolve the cache store.
     *
     * @param  self  $attribute
     * @param  \Heritage\Contracts\Container\Container  $container
     * @return \Heritage\Contracts\Cache\Repository
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $attribute->memo
            ? $container->make('cache')->memo($attribute->store)
            : $container->make('cache')->store($attribute->store);
    }
}
