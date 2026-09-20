<?php

namespace Heritage\Database;

use Heritage\Console\Command;
use Heritage\Console\View\Components\TwoColumnDetail;
use Heritage\Contracts\Container\Container;
use Heritage\Database\Console\Seeds\WithoutModelEvents;
use Heritage\Support\Arr;
use InvalidArgumentException;

abstract class Seeder
{
    /**
     * The container instance.
     *
     * @var \Heritage\Contracts\Container\Container
     */
    protected $container;

    /**
     * The console command instance.
     *
     * @var \Heritage\Console\Command
     */
    protected $command;

    /**
     * Seeders that have been called at least one time.
     *
     * @var array
     */
    protected static $called = [];

    /**
     * The registered artifact seeders registry.
     *
     * @var array<string, class-string<\Heritage\Database\Seeder>>
     */
    protected static array $artifactSeeders = [];

    /**
     * Backward-compatible alias for registered artifact seeders.
     *
     * @var array<string, class-string<\Heritage\Database\Seeder>>
     */
    protected static array $moduleSeeders = [];

    /**
     * Run the given seeder class.
     *
     * @param  array|string  $class
     * @param  bool  $silent
     * @param  array  $parameters
     * @return $this
     */
    public function call($class, $silent = false, array $parameters = [])
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            $seeder = $this->resolve($class);

            $name = get_class($seeder);

            if ($silent === false && isset($this->command)) {
                (new TwoColumnDetail($this->command->getOutput()))
                    ->render($name, '<fg=yellow;options=bold>RUNNING</>');
            }

            $startTime = microtime(true);

            $seeder->__invoke($parameters);

            if ($silent === false && isset($this->command)) {
                $runTime = number_format((microtime(true) - $startTime) * 1000);

                (new TwoColumnDetail($this->command->getOutput()))
                    ->render($name, "<fg=gray>$runTime ms</> <fg=green;options=bold>DONE</>");

                $this->command->getOutput()->writeln('');
            }

            static::$called[] = $class;
        }

        return $this;
    }

    /**
     * Run the given seeder class.
     *
     * @param  array|string  $class
     * @param  array  $parameters
     * @return void
     */
    public function callWith($class, array $parameters = [])
    {
        $this->call($class, false, $parameters);
    }

    /**
     * Silently run the given seeder class.
     *
     * @param  array|string  $class
     * @param  array  $parameters
     * @return void
     */
    public function callSilent($class, array $parameters = [])
    {
        $this->call($class, true, $parameters);
    }

    /**
     * Run the given seeder class once.
     *
     * @param  array|string  $class
     * @param  bool  $silent
     * @return void
     */
    public function callOnce($class, $silent = false, array $parameters = [])
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            if (in_array($class, static::$called)) {
                continue;
            }

            $this->call($class, $silent, $parameters);
        }
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param  string  $class
     * @return \Heritage\Database\Seeder
     */
    protected function resolve($class)
    {
        if (isset($this->container)) {
            $instance = $this->container->make($class);

            $instance->setContainer($this->container);
        } else {
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command);
        }

        return $instance;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Heritage\Contracts\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
     *
     * @param  \Heritage\Console\Command  $command
     * @return $this
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Run the database seeds.
     *
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __invoke(array $parameters = [])
    {
        if (! method_exists($this, 'run')) {
            throw new InvalidArgumentException('Method [run] missing from '.get_class($this));
        }

        $callback = fn () => isset($this->container)
            ? $this->container->call([$this, 'run'], $parameters)
            : $this->run(...$parameters);

        if (isset(class_uses_recursive(static::class)[WithoutModelEvents::class])) {
            $callback = $this->withoutModelEvents($callback);
        }

        return $callback();
    }

    /**
     * Register an artifact seeder in the global seeder registry.
     *
     * @param  string  $artifact
     * @param  class-string<\Heritage\Database\Seeder>  $seederClass
     * @return void
     */
    public static function registerArtifactSeeder(string $artifact, string $seederClass): void
    {
        static::$artifactSeeders[$artifact] = $seederClass;
        static::$moduleSeeders[$artifact] = $seederClass;
    }

    /**
     * Get all registered artifact seeders.
     *
     * @return array<string, class-string<\Heritage\Database\Seeder>>
     */
    public static function getArtifactSeeders(): array
    {
        return static::$artifactSeeders;
    }

    /**
     * Get the seeder class registered for the given artifact.
     *
     * @param  string  $artifact
     * @return class-string<\Heritage\Database\Seeder>|null
     */
    public static function getArtifactSeeder(string $artifact): ?string
    {
        return static::$artifactSeeders[$artifact] ?? null;
    }

    /**
     * @deprecated Use registerArtifactSeeder instead.
     */
    public static function registerModuleSeeder(string $module, string $seederClass): void
    {
        static::registerArtifactSeeder($module, $seederClass);
    }

    /**
     * @deprecated Use getArtifactSeeders instead.
     */
    public static function getModuleSeeders(): array
    {
        return static::getArtifactSeeders();
    }

    /**
     * @deprecated Use getArtifactSeeder instead.
     */
    public static function getModuleSeeder(string $module): ?string
    {
        return static::getArtifactSeeder($module);
    }
}
