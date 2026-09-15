<?php

namespace Heritage\Support\Facades;

/**
 * @method static \Heritage\Hashing\BcryptHasher createBcryptDriver()
 * @method static \Heritage\Hashing\ArgonHasher createArgonDriver()
 * @method static \Heritage\Hashing\Argon2IdHasher createArgon2idDriver()
 * @method static array info(string $hashedValue)
 * @method static string make(string $value, array $options = [])
 * @method static bool check(string $value, string $hashedValue, array $options = [])
 * @method static bool needsRehash(string $hashedValue, array $options = [])
 * @method static bool isHashed(string $value)
 * @method static string getDefaultDriver()
 * @method static mixed driver(\UnitEnum|string|null $driver = null)
 * @method static \Heritage\Hashing\HashManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Heritage\Contracts\Container\Container getContainer()
 * @method static \Heritage\Hashing\HashManager setContainer(\Heritage\Contracts\Container\Container $container)
 * @method static \Heritage\Hashing\HashManager forgetDrivers()
 *
 * @see \Heritage\Hashing\HashManager
 * @see \Heritage\Hashing\AbstractHasher
 */
class Hash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'hash';
    }
}
