<?php

declare(strict_types=1);

namespace Heritage\Translation;

use Heritage\Container\Container;
use Heritage\Contracts\Translation\LocaleResolver;
use WeakMap;

/**
 * Access point to the active LocaleResolver.
 *
 * Resolution order:
 *  1. the class named in config('locale.resolver'), when set;
 *  2. the resolver an artifact bound to the LocaleResolver contract (the i18n artifact binds
 *     its database resolver);
 *  3. ConfigLocaleResolver (config/locale.php), so the kernel works without i18n.
 *
 * The resolver is resolved once per container (translatable models ask for it on every
 * attribute read); call flush() after changing the binding or config at runtime.
 */
final class Locales
{
    /**
     * @var WeakMap<Container, LocaleResolver>|null
     */
    private static ?WeakMap $resolved = null;

    public static function resolver(): LocaleResolver
    {
        $app = Container::getInstance();

        self::$resolved ??= new WeakMap;

        return self::$resolved[$app] ??= self::resolve($app);
    }

    /**
     * Forget the cached resolvers (e.g. after rebinding the contract in tests).
     */
    public static function flush(): void
    {
        self::$resolved = null;
    }

    private static function resolve(Container $app): LocaleResolver
    {
        if ($app->bound('config') && is_string($class = $app['config']->get('locale.resolver')) && $class !== '') {
            $app->singletonIf($class);

            return $app->make($class);
        }

        if ($app->bound(LocaleResolver::class)) {
            return $app->make(LocaleResolver::class);
        }

        return new ConfigLocaleResolver;
    }
}
