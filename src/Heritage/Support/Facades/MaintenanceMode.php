<?php

namespace Heritage\Support\Facades;

use Heritage\Foundation\MaintenanceModeManager;

/**
 * @method static string getDefaultDriver()
 * @method static mixed driver(\UnitEnum|string|null $driver = null)
 * @method static \Heritage\Foundation\MaintenanceModeManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Heritage\Contracts\Container\Container getContainer()
 * @method static \Heritage\Foundation\MaintenanceModeManager setContainer(\Heritage\Contracts\Container\Container $container)
 * @method static \Heritage\Foundation\MaintenanceModeManager forgetDrivers()
 *
 * @see \Heritage\Foundation\MaintenanceModeManager
 */
class MaintenanceMode extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return MaintenanceModeManager::class;
    }
}
