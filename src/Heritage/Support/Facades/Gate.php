<?php

namespace Heritage\Support\Facades;

use Heritage\Contracts\Auth\Access\Gate as GateContract;

/**
 * @method static bool has(\UnitEnum|array|string $ability)
 * @method static \Heritage\Auth\Access\Response allowIf(\Heritage\Auth\Access\Response|\Closure|bool $condition, string|null $message = null, string|null $code = null)
 * @method static \Heritage\Auth\Access\Response denyIf(\Heritage\Auth\Access\Response|\Closure|bool $condition, string|null $message = null, string|null $code = null)
 * @method static \Heritage\Auth\Access\Gate define(\UnitEnum|string $ability, callable|array|string $callback)
 * @method static \Heritage\Auth\Access\Gate resource(string $name, string $class, array|null $abilities = null)
 * @method static \Heritage\Auth\Access\Gate policy(string $class, string $policy)
 * @method static \Heritage\Auth\Access\Gate before(callable $callback)
 * @method static \Heritage\Auth\Access\Gate after(callable $callback)
 * @method static bool allows(iterable|\UnitEnum|string $ability, mixed $arguments = [])
 * @method static bool denies(iterable|\UnitEnum|string $ability, mixed $arguments = [])
 * @method static bool check(iterable|\UnitEnum|string $abilities, mixed $arguments = [])
 * @method static bool any(iterable|\UnitEnum|string $abilities, mixed $arguments = [])
 * @method static bool none(iterable|\UnitEnum|string $abilities, mixed $arguments = [])
 * @method static \Heritage\Auth\Access\Response authorize(\UnitEnum|string $ability, mixed $arguments = [])
 * @method static \Heritage\Auth\Access\Response inspect(\UnitEnum|string $ability, mixed $arguments = [])
 * @method static mixed raw(string $ability, mixed $arguments = [])
 * @method static mixed getPolicyFor(object|string $class)
 * @method static \Heritage\Auth\Access\Gate guessPolicyNamesUsing(callable $callback)
 * @method static mixed resolvePolicy(object|string $class)
 * @method static \Heritage\Auth\Access\Gate forUser(\Heritage\Contracts\Auth\Authenticatable|mixed $user)
 * @method static array abilities()
 * @method static array policies()
 * @method static \Heritage\Auth\Access\Gate defaultDenialResponse(\Heritage\Auth\Access\Response $response)
 * @method static \Heritage\Auth\Access\Gate setContainer(\Heritage\Contracts\Container\Container $container)
 * @method static \Heritage\Auth\Access\Response denyWithStatus(int $status, string|null $message = null, int|null $code = null)
 * @method static \Heritage\Auth\Access\Response denyAsNotFound(string|null $message = null, int|null $code = null)
 *
 * @see \Heritage\Auth\Access\Gate
 */
class Gate extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return GateContract::class;
    }
}
