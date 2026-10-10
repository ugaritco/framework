<?php

declare(strict_types=1);

namespace Heritage\Database\Eloquent\Concerns;

/**
 * Trait providing native multilingual and translation capabilities for Eloquent Models.
 *
 * @deprecated Use \Heritage\Database\Eloquent\Concerns\Translatable instead.
 */
trait HasTranslation
{
    use Translatable;

    /**
     * Boot the translation trait for backwards compatibility.
     */
    public static function bootHasTranslation(): void
    {
        static::bootTranslatable();
    }
}
