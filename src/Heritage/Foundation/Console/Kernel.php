<?php

namespace Heritage\Foundation\Console;

use Carbon\CarbonInterval;
use Closure;
use DateTimeInterface;
use Heritage\Console\Application as Scribe;
use Heritage\Console\Command;
use Heritage\Console\Events\CommandFinished;
use Heritage\Console\Events\CommandStarting;
use Heritage\Console\Scheduling\Schedule;
use Heritage\Contracts\Console\Kernel as KernelContract;
use Heritage\Contracts\Debug\ExceptionHandler;
use Heritage\Contracts\Events\Dispatcher;
use Heritage\Contracts\Foundation\Application;
use Heritage\Foundation\Events\Terminating;
use Heritage\Support\Arr;
use Heritage\Support\Carbon;
use Heritage\Support\Collection;
use Heritage\Support\Env;
use Heritage\Support\InteractsWithTime;
use Heritage\Support\Str;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Throwable;
use WeakMap;

class Kernel implements KernelContract
{
    use InteractsWithTime;

    /**
     * The application implementation.
     *
     * @var \Heritage\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The event dispatcher implementation.
     *
     * @var \Heritage\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The Symfony event dispatcher implementation.
     *
     * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|null
     */
    protected $symfonyDispatcher;

    /**
     * The Scribe application instance.
     *
     * @var \Heritage\Console\Application|null
     */
    protected $scribe;

    /**
     * The Scribe commands provided by the application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * The paths where Scribe commands should be automatically discovered.
     *
     * @var array
     */
    protected $commandPaths = [];

    /**
     * The paths where Scribe "routes" should be automatically discovered.
     *
     * @var array
     */
    protected $commandRoutePaths = [];

    /**
     * Indicates if the Closure commands have been loaded.
     *
     * @var bool
     */
    protected $commandsLoaded = false;

    /**
     * The commands paths that have been "loaded".
     *
     * @var array
     */
    protected $loadedPaths = [];

    /**
     * All of the registered command duration handlers.
     *
     * @var array
     */
    protected $commandLifecycleDurationHandlers = [];

    /**
     * When the currently handled command started.
     *
     * @var \Heritage\Support\Carbon|null
     */
    protected $commandStartedAt;

    /**
     * The bootstrap classes for the application.
     *
     * @var string[]
     */
    protected $bootstrappers = [
        \Heritage\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Heritage\Foundation\Bootstrap\LoadConfiguration::class,
        \Heritage\Foundation\Bootstrap\HandleExceptions::class,
        \Heritage\Foundation\Bootstrap\RegisterFacades::class,
        \Heritage\Foundation\Bootstrap\SetRequestForConsole::class,
        \Heritage\Foundation\Bootstrap\RegisterProviders::class,
        \Heritage\Foundation\Bootstrap\BootProviders::class,
    ];

    /**
     * Create a new console kernel instance.
     *
     * @param  \Heritage\Contracts\Foundation\Application  $app
     * @param  \Heritage\Contracts\Events\Dispatcher  $events
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        if (! defined('SCRIBE_BINARY')) {
            define('SCRIBE_BINARY', 'scribe');
        }

        $this->app = $app;
        $this->events = $events;

        $this->app->booted(function () {
            if (! $this->app->runningUnitTests()) {
                $this->rerouteSymfonyCommandEvents();
            }
        });
    }

    /**
     * Re-route the Symfony command events to their Ugarit counterparts.
     *
     * @internal
     *
     * @return $this
     */
    public function rerouteSymfonyCommandEvents()
    {
        if (is_null($this->symfonyDispatcher)) {
            $this->symfonyDispatcher = new EventDispatcher;

            $this->symfonyDispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
                $this->events->dispatch(
                    new CommandStarting($event->getCommand()?->getName() ?? '', $event->getInput(), $event->getOutput())
                );
            });

            $this->symfonyDispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
                $this->events->dispatch(
                    new CommandFinished($event->getCommand()?->getName() ?? '', $event->getInput(), $event->getOutput(), $event->getExitCode())
                );
            });
        }

        return $this;
    }

    /**
     * Run the console application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $output
     * @return int
     */
    public function handle($input, $output = null)
    {
        $this->commandStartedAt = Carbon::now();

        try {
            if (in_array($input->getFirstArgument(), ['env:encrypt', 'env:decrypt'], true)) {
                $this->bootstrapWithoutBootingProviders();
            }

            $this->bootstrap();

            return $this->getScribe()->run($input, $output);
        } catch (Throwable $e) {
            $this->reportException($e);

            $this->renderException($output, $e);

            return 1;
        }
    }

    /**
     * Terminate the application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  int  $status
     * @return void
     */
    public function terminate($input, $status)
    {
        $this->events->dispatch(new Terminating);

        $this->app->terminate();

        if ($this->commandStartedAt === null) {
            return;
        }

        $timezone = $this->app->bound('config') ? ($this->app['config']->get('app.timezone') ?? 'UTC') : 'UTC';

        $this->commandStartedAt->setTimezone($timezone);

        foreach ($this->commandLifecycleDurationHandlers as ['threshold' => $threshold, 'handler' => $handler]) {
            $end ??= Carbon::now();

            if ($this->commandStartedAt->diffInMilliseconds($end) > $threshold) {
                $handler($this->commandStartedAt, $input, $status);
            }
        }

        $this->commandStartedAt = null;
    }

    /**
     * Register a callback to be invoked when the command lifecycle duration exceeds a given amount of time.
     *
     * @param  \DateTimeInterface|\Carbon\CarbonInterval|float|int  $threshold
     * @param  callable  $handler
     * @return void
     */
    public function whenCommandLifecycleIsLongerThan($threshold, $handler)
    {
        $threshold = $threshold instanceof DateTimeInterface
            ? $this->secondsUntil($threshold) * 1000
            : $threshold;

        $threshold = $threshold instanceof CarbonInterval
            ? $threshold->totalMilliseconds
            : $threshold;

        $this->commandLifecycleDurationHandlers[] = [
            'threshold' => $threshold,
            'handler' => $handler,
        ];
    }

    /**
     * When the command being handled started.
     *
     * @return \Heritage\Support\Carbon|null
     */
    public function commandStartedAt()
    {
        return $this->commandStartedAt;
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Heritage\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }

    /**
     * Resolve a console schedule instance.
     *
     * @return \Heritage\Console\Scheduling\Schedule
     */
    public function resolveConsoleSchedule()
    {
        return tap(new Schedule($this->scheduleTimezone()), function ($schedule) {
            $this->schedule($schedule->useCache($this->scheduleCache()));
        });
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone()
    {
        $config = $this->app['config'];

        return $config->get('app.schedule_timezone', $config->get('app.timezone'));
    }

    /**
     * Get the name of the cache store that should manage scheduling mutexes.
     *
     * @return string|null
     */
    protected function scheduleCache()
    {
        return $this->app['config']->get('cache.schedule_store', Env::get('SCHEDULE_CACHE_DRIVER', function () {
            return Env::get('SCHEDULE_CACHE_STORE');
        }));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        //
    }

    /**
     * Register a Closure based command with the application.
     *
     * @param  string  $signature
     * @param  \Closure  $callback
     * @return \Heritage\Foundation\Console\ClosureCommand
     */
    public function command($signature, Closure $callback)
    {
        $command = new ClosureCommand($signature, $callback);

        Scribe::starting(function ($scribe) use ($command) {
            $scribe->add($command);
        });

        return $command;
    }

    /**
     * Register all of the commands in the given directory.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function load($paths)
    {
        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        $this->loadedPaths = array_values(
            array_unique(array_merge($this->loadedPaths, $paths))
        );

        $namespace = $this->app->getNamespace();

        $possibleCommands = new WeakMap;

        $filterCommands = function (SplFileInfo $file) use ($namespace, &$possibleCommands) {
            $commandClassName = $this->commandClassFromFile($file, $namespace);

            $possibleCommands[$file] = $commandClassName;

            $command = rescue(fn () => new ReflectionClass($commandClassName), null, false);

            return $command instanceof ReflectionClass
                && $command->isSubClassOf(Command::class)
                && ! $command->isAbstract();
        };

        foreach ($this->findCommands($paths)->filter($filterCommands) as $file) {
            Scribe::starting(function ($scribe) use ($file, $possibleCommands) {
                $scribe->resolve($possibleCommands[$file]);
            });
        }
    }

    /**
     * Get the Finder instance for discovering command files.
     *
     * @param  array  $paths
     * @return \Symfony\Component\Finder\Finder
     */
    protected function findCommands(array $paths)
    {
        return Finder::create()->in($paths)->name('*.php')->files();
    }

    /**
     * Extract the command class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $namespace
     * @return string
     */
    protected function commandClassFromFile(SplFileInfo $file, string $namespace): string
    {
        return $namespace.str_replace(
            ['/', '.php'],
            ['\\', ''],
            Str::after($file->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
        );
    }

    /**
     * Register the given command with the console application.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return void
     */
    public function registerCommand($command)
    {
        $this->getScribe()->add($command);
    }

    /**
     * Run an Scribe console command by name.
     *
     * @param  \Symfony\Component\Console\Command\Command|string  $command
     * @param  array  $parameters
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
     * @return int
     *
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        if (in_array($command, ['env:encrypt', 'env:decrypt'], true)) {
            $this->bootstrapWithoutBootingProviders();
        }

        $this->bootstrap();

        return $this->getScribe()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Queue the given console command.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Heritage\Foundation\Bus\PendingDispatch
     */
    public function queue($command, array $parameters = [])
    {
        return QueuedCommand::dispatch(func_get_args());
    }

    /**
     * Get the registered command instance with the given name, if any.
     *
     * @param  string  $name
     * @return \Symfony\Component\Console\Command\Command|null
     */
    public function findCommand($name)
    {
        $scribe = $this->getScribe();

        return $scribe->has($name) ? $scribe->get($name) : null;
    }

    /**
     * Get all of the commands registered with the console.
     *
     * @return array
     */
    public function all()
    {
        $this->bootstrap();

        return $this->getScribe()->all();
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        $this->bootstrap();

        return $this->getScribe()->output();
    }

    /**
     * Bootstrap the application for scribe commands.
     *
     * @return void
     */
    public function bootstrap()
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }

        $this->app->loadDeferredProviders();

        if (! $this->commandsLoaded) {
            $this->commands();

            if ($this->shouldDiscoverCommands()) {
                $this->discoverCommands();
            }

            $this->commandsLoaded = true;
        }
    }

    /**
     * Discover the commands that should be automatically loaded.
     *
     * @return void
     */
    protected function discoverCommands()
    {
        foreach ($this->commandPaths as $path) {
            $this->load($path);
        }

        foreach ($this->commandRoutePaths as $path) {
            if (file_exists($path)) {
                require $path;
            }
        }
    }

    /**
     * Bootstrap the application without booting service providers.
     *
     * @return void
     */
    public function bootstrapWithoutBootingProviders()
    {
        $this->app->bootstrapWith(
            (new Collection($this->bootstrappers()))
                ->reject(fn ($bootstrapper) => $bootstrapper === \Heritage\Foundation\Bootstrap\BootProviders::class)
                ->all()
        );
    }

    /**
     * Determine if the kernel should discover commands.
     *
     * @return bool
     */
    protected function shouldDiscoverCommands()
    {
        return get_class($this) === __CLASS__;
    }

    /**
     * Get the Scribe application instance.
     *
     * @return \Heritage\Console\Application
     */
    protected function getScribe()
    {
        if (is_null($this->scribe)) {
            $this->scribe = (new Scribe($this->app, $this->events, $this->app->version()))
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader();

            if ($this->symfonyDispatcher instanceof EventDispatcher) {
                $this->scribe->setDispatcher($this->symfonyDispatcher);
                $this->scribe->setSignalsToDispatchEvent();
            }
        }

        return $this->scribe;
    }

    /**
     * Set the Scribe application instance.
     *
     * @param  \Heritage\Console\Application|null  $scribe
     * @return void
     */
    public function setScribe($scribe)
    {
        $this->scribe = $scribe;
    }

    /**
     * Set the Scribe commands provided by the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function addCommands(array $commands)
    {
        $this->commands = array_values(array_unique(array_merge($this->commands, $commands)));

        return $this;
    }

    /**
     * Set the paths that should have their Scribe commands automatically discovered.
     *
     * @param  array  $paths
     * @return $this
     */
    public function addCommandPaths(array $paths)
    {
        $this->commandPaths = array_values(array_unique(array_merge($this->commandPaths, $paths)));

        return $this;
    }

    /**
     * Set the paths that should have their Scribe "routes" automatically discovered.
     *
     * @param  array  $paths
     * @return $this
     */
    public function addCommandRoutePaths(array $paths)
    {
        $this->commandRoutePaths = array_values(array_unique(array_merge($this->commandRoutePaths, $paths)));

        return $this;
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function reportException(Throwable $e)
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the given exception.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderException($output, Throwable $e)
    {
        $this->app[ExceptionHandler::class]->renderForConsole($output, $e);
    }
}
