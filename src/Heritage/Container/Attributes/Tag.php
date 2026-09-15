<?php

declare(strict_types=1);

namespace Heritage\Container\Attributes;

use Attribute;
use Heritage\Contracts\Container\Container;
use Heritage\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Tag implements ContextualAttribute
{
    public function __construct(
        public string $tag,
    ) {
    }

    /**
     * Resolve the tag.
     *
     * @param  self  $attribute
     * @param  \Heritage\Contracts\Container\Container  $container
     * @return mixed
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->tagged($attribute->tag);
    }
}
