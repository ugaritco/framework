<?php

namespace Heritage\Support\Facades;

use Ugarit\Ui\UiServiceProvider;
use RuntimeException;

/**
 * @method static \Heritage\Contracts\Auth\Guard|\Heritage\Contracts\Auth\StatefulGuard guard(\UnitEnum|string|null $name = null)
 * @method static \Heritage\Auth\SessionGuard createSessionDriver(string $name, array $config)
 * @method static \Heritage\Auth\TokenGuard createTokenDriver(string $name, array $config)
 * @method static string getDefaultDriver()
 * @method static void shouldUse(\UnitEnum|string|null $name)
 * @method static void setDefaultDriver(\UnitEnum|string $name)
 * @method static \Heritage\Auth\AuthManager viaRequest(string $driver, callable $callback)
 * @method static \Closure userResolver()
 * @method static \Heritage\Auth\AuthManager resolveUsersUsing(\Closure $userResolver)
 * @method static \Heritage\Auth\AuthManager extend(string $driver, \Closure $callback)
 * @method static \Heritage\Auth\AuthManager provider(string $name, \Closure $callback)
 * @method static bool hasResolvedGuards()
 * @method static \Heritage\Auth\AuthManager forgetGuards()
 * @method static \Heritage\Auth\AuthManager setApplication(\Heritage\Contracts\Foundation\Application $app)
 * @method static \Heritage\Contracts\Auth\UserProvider|null createUserProvider(string|null $provider = null)
 * @method static string getDefaultUserProvider()
 * @method static bool check()
 * @method static bool guest()
 * @method static \Heritage\Contracts\Auth\Authenticatable|null user()
 * @method static int|string|null id()
 * @method static bool validate(array $credentials = [])
 * @method static bool hasUser()
 * @method static \Heritage\Contracts\Auth\Guard setUser(\Heritage\Contracts\Auth\Authenticatable $user)
 * @method static bool attempt(array $credentials = [], bool $remember = false)
 * @method static bool once(array $credentials = [])
 * @method static void login(\Heritage\Contracts\Auth\Authenticatable $user, bool $remember = false)
 * @method static \Heritage\Contracts\Auth\Authenticatable|false loginUsingId(mixed $id, bool $remember = false)
 * @method static \Heritage\Contracts\Auth\Authenticatable|false onceUsingId(mixed $id)
 * @method static bool viaRemember()
 * @method static void logout()
 * @method static \Symfony\Component\HttpFoundation\Response|null basic(string $field = 'email', array $extraConditions = [])
 * @method static \Symfony\Component\HttpFoundation\Response|null onceBasic(string $field = 'email', array $extraConditions = [])
 * @method static bool attemptWhen(array $credentials = [], array|callable|null $callbacks = null, bool $remember = false)
 * @method static string hashPasswordForCookie(string $passwordHash)
 * @method static void logoutCurrentDevice()
 * @method static \Heritage\Contracts\Auth\Authenticatable|null logoutOtherDevices(string $password)
 * @method static void attempting(mixed $callback)
 * @method static \Heritage\Contracts\Auth\Authenticatable getLastAttempted()
 * @method static string getName()
 * @method static string getRecallerName()
 * @method static \Heritage\Auth\SessionGuard setRememberDuration(int $minutes)
 * @method static \Heritage\Contracts\Cookie\QueueingFactory getCookieJar()
 * @method static void setCookieJar(\Heritage\Contracts\Cookie\QueueingFactory $cookie)
 * @method static \Heritage\Contracts\Events\Dispatcher getDispatcher()
 * @method static void setDispatcher(\Heritage\Contracts\Events\Dispatcher $events)
 * @method static \Heritage\Contracts\Session\Session getSession()
 * @method static \Heritage\Contracts\Auth\Authenticatable|null getUser()
 * @method static \Symfony\Component\HttpFoundation\Request getRequest()
 * @method static \Heritage\Auth\SessionGuard setRequest(\Symfony\Component\HttpFoundation\Request $request)
 * @method static \Heritage\Support\Timebox getTimebox()
 * @method static \Heritage\Contracts\Auth\Authenticatable authenticate()
 * @method static \Heritage\Auth\SessionGuard forgetUser()
 * @method static \Heritage\Contracts\Auth\UserProvider getProvider()
 * @method static void setProvider(\Heritage\Contracts\Auth\UserProvider $provider)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Heritage\Auth\AuthManager
 * @see \Heritage\Auth\SessionGuard
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth';
    }

    /**
     * Register the typical authentication routes for an application.
     *
     * @param  array  $options
     * @return void
     *
     * @throws \RuntimeException
     */
    public static function routes(array $options = [])
    {
        if (! static::$app->providerIsLoaded(UiServiceProvider::class)) {
            throw new RuntimeException('In order to use the Auth::routes() method, please install the ugarit/ui package.');
        }

        static::$app->make('router')->auth($options);
    }
}
