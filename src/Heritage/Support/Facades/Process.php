<?php

namespace Heritage\Support\Facades;

use Closure;
use Heritage\Process\Factory;

/**
 * @method static \Heritage\Process\PendingProcess command(array|string $command)
 * @method static \Heritage\Process\PendingProcess path(string $path)
 * @method static \Heritage\Process\PendingProcess timeout(\Carbon\CarbonInterval|int $timeout)
 * @method static \Heritage\Process\PendingProcess idleTimeout(\Carbon\CarbonInterval|int $timeout)
 * @method static \Heritage\Process\PendingProcess forever()
 * @method static \Heritage\Process\PendingProcess env(array $environment)
 * @method static \Heritage\Process\PendingProcess input(\Traversable|resource|string|int|float|bool|null $input)
 * @method static \Heritage\Process\PendingProcess quietly()
 * @method static \Heritage\Process\PendingProcess tty(bool $tty = true)
 * @method static \Heritage\Process\PendingProcess options(array $options)
 * @method static \Heritage\Contracts\Process\ProcessResult run(array|string|null $command = null, callable|null $output = null)
 * @method static \Heritage\Process\InvokedProcess start(array|string|null $command = null, callable|null $output = null)
 * @method static bool supportsTty()
 * @method static \Heritage\Process\PendingProcess withFakeHandlers(array $fakeHandlers)
 * @method static \Heritage\Process\PendingProcess|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Heritage\Process\PendingProcess|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Heritage\Process\FakeProcessResult result(array|string $output = '', array|string $errorOutput = '', int $exitCode = 0)
 * @method static \Heritage\Process\FakeProcessDescription describe()
 * @method static \Heritage\Process\FakeProcessSequence sequence(array $processes = [])
 * @method static bool isRecording()
 * @method static \Heritage\Process\Factory recordIfRecording(\Heritage\Process\PendingProcess $process, \Heritage\Contracts\Process\ProcessResult $result)
 * @method static \Heritage\Process\Factory record(\Heritage\Process\PendingProcess $process, \Heritage\Contracts\Process\ProcessResult $result)
 * @method static \Heritage\Process\Factory preventStrayProcesses(bool $prevent = true)
 * @method static bool preventingStrayProcesses()
 * @method static \Heritage\Process\Factory assertRan(\Closure|array|string $callback)
 * @method static \Heritage\Process\Factory assertRanTimes(\Closure|array|string $callback, int $times = 1)
 * @method static \Heritage\Process\Factory assertRanInOrder(array $callbacks)
 * @method static \Heritage\Process\Factory assertNotRan(\Closure|array|string $callback)
 * @method static \Heritage\Process\Factory assertDidntRun(\Closure|array|string $callback)
 * @method static \Heritage\Process\Factory assertNothingRan()
 * @method static \Heritage\Process\Pool pool(callable $callback)
 * @method static \Heritage\Contracts\Process\ProcessResult pipe(callable|array $callback, callable|null $output = null)
 * @method static \Heritage\Process\ProcessPoolResults concurrently(callable $callback, callable|null $output = null)
 * @method static \Heritage\Process\PendingProcess newPendingProcess()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 *
 * @see \Heritage\Process\PendingProcess
 * @see \Heritage\Process\Factory
 */
class Process extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }

    /**
     * Indicate that the process factory should fake processes.
     *
     * @param  \Closure|array|null  $callback
     * @return \Heritage\Process\Factory
     */
    public static function fake(Closure|array|null $callback = null)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }
}
