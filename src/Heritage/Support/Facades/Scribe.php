<?php

namespace Heritage\Support\Facades;

use Heritage\Contracts\Console\Kernel as ConsoleKernelContract;

/**
 * @method static int handle(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface|null $output = null)
 * @method static void terminate(\Symfony\Component\Console\Input\InputInterface $input, int $status)
 * @method static void whenCommandLifecycleIsLongerThan(\DateTimeInterface|\Carbon\CarbonInterval|float|int $threshold, callable $handler)
 * @method static \Heritage\Support\Carbon|null commandStartedAt()
 * @method static \Heritage\Console\Scheduling\Schedule resolveConsoleSchedule()
 * @method static \Heritage\Foundation\Console\ClosureCommand command(string $signature, \Closure $callback)
 * @method static void registerCommand(\Symfony\Component\Console\Command\Command $command)
 * @method static int call(\Symfony\Component\Console\Command\Command|string $command, array $parameters = [], \Symfony\Component\Console\Output\OutputInterface|null $outputBuffer = null)
 * @method static \Heritage\Foundation\Bus\PendingDispatch queue(string $command, array $parameters = [])
 * @method static \Symfony\Component\Console\Command\Command|null findCommand(string $name)
 * @method static array all()
 * @method static string output()
 * @method static void bootstrap()
 * @method static void bootstrapWithoutBootingProviders()
 * @method static void setScribe(\Heritage\Console\Application|null $scribe)
 * @method static \Heritage\Foundation\Console\Kernel addCommands(array $commands)
 * @method static \Heritage\Foundation\Console\Kernel addCommandPaths(array $paths)
 * @method static \Heritage\Foundation\Console\Kernel addCommandRoutePaths(array $paths)
 *
 * @see \Heritage\Foundation\Console\Kernel
 */
class Scribe extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConsoleKernelContract::class;
    }
}
