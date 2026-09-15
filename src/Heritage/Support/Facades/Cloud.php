<?php

namespace Heritage\Support\Facades;

use Heritage\Foundation\Cloud\CloudManager;

/**
 * @method static bool hosted()
 * @method static bool usesManagedQueues()
 * @method static \Heritage\Foundation\Cloud\Queue queue()
 * @method static bool isManagedQueue(string $queue)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Heritage\Foundation\Cloud\CloudManager
 */
class Cloud extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return CloudManager::class;
    }
}
