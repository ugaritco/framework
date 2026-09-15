<?php

namespace Heritage\Support\Facades;

use Heritage\Concurrency\ConcurrencyManager;

/**
 * @method static mixed driver(\UnitEnum|string|null $name = null)
 * @method static \Heritage\Concurrency\ProcessDriver createProcessDriver()
 * @method static \Heritage\Concurrency\ForkDriver createForkDriver()
 * @method static \Heritage\Concurrency\SyncDriver createSyncDriver()
 * @method static string getDefaultInstance()
 * @method static void setDefaultInstance(string $name)
 * @method static array getInstanceConfig(string $name)
 * @method static mixed instance(string|null $name = null)
 * @method static \Heritage\Concurrency\ConcurrencyManager forgetInstance(array|string|null $name = null)
 * @method static void purge(string|null $name = null)
 * @method static \Heritage\Concurrency\ConcurrencyManager extend(string $name, \Closure $callback)
 * @method static \Heritage\Concurrency\ConcurrencyManager setApplication(\Heritage\Contracts\Foundation\Application $app)
 * @method static array run(\Closure|array $tasks, \Carbon\CarbonInterval|int|null $timeout = null)
 * @method static \Heritage\Support\Defer\DeferredCallback defer(\Closure|array $tasks)
 *
 * @see \Heritage\Concurrency\ConcurrencyManager
 */
class Concurrency extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConcurrencyManager::class;
    }
}
