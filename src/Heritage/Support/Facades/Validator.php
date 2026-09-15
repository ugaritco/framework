<?php

namespace Heritage\Support\Facades;

/**
 * @method static \Heritage\Validation\Validator make(array $data, array $rules, array $messages = [], array $attributes = [])
 * @method static array validate(array $data, array $rules, array $messages = [], array $attributes = [])
 * @method static void extend(string $rule, \Closure|string $extension, string|null $message = null)
 * @method static void extendImplicit(string $rule, \Closure|string $extension, string|null $message = null)
 * @method static void extendDependent(string $rule, \Closure|string $extension, string|null $message = null)
 * @method static void replacer(string $rule, \Closure|string $replacer)
 * @method static void includeUnvalidatedArrayKeys()
 * @method static void excludeUnvalidatedArrayKeys()
 * @method static void fakeDnsLookups(bool $value = true)
 * @method static void resolver(\Closure $resolver)
 * @method static \Heritage\Contracts\Translation\Translator getTranslator()
 * @method static \Heritage\Validation\PresenceVerifierInterface getPresenceVerifier()
 * @method static void setPresenceVerifier(\Heritage\Validation\PresenceVerifierInterface $presenceVerifier)
 * @method static \Heritage\Contracts\Container\Container|null getContainer()
 * @method static \Heritage\Validation\Factory setContainer(\Heritage\Contracts\Container\Container $container)
 *
 * @see \Heritage\Validation\Factory
 */
class Validator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'validator';
    }
}
