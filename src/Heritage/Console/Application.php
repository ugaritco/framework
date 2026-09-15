<?php

namespace Heritage\Console;

use Closure;
use Heritage\Console\Events\ScribeStarting;
use Heritage\Contracts\Console\Application as ApplicationContract;
use Heritage\Contracts\Container\Container;
use Heritage\Contracts\Events\Dispatcher;
use Heritage\Support\ProcessUtils;
use ReflectionClass;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function Heritage\Support\scribe_binary;
use function Heritage\Support\php_binary;

class Application extends SymfonyApplication implements ApplicationContract
{
    /**
     * The Ugarit application instance.
     *
     * @var \Heritage\Contracts\Container\Container
     */
    protected $ugarit;

    /**
     * The event dispatcher instance.
     *
     * @var \Heritage\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The output from the previous command.
     *
     * @var \Symfony\Component\Console\Output\BufferedOutput
     */
    protected $lastOutput;

    /**
     * The console application bootstrappers.
     *
     * @var array<array-key, \Closure($this): void>
     */
    protected static $bootstrappers = [];

    /**
     * A map of command names to classes.
     *
     * @var array<string, \Heritage\Console\Command|string>
     */
    protected $commandMap = [];

    /**
     * Create a new Scribe console application.
     *
     * @param  \Heritage\Contracts\Container\Container  $ugarit
     * @param  \Heritage\Contracts\Events\Dispatcher  $events
     * @param  string  $version
     */
    public function __construct(Container $ugarit, Dispatcher $events, $version)
    {
        parent::__construct('Ugarit Framework', $version);

        $this->ugarit = $ugarit;
        $this->events = $events;
        $this->setAutoExit(false);
        $this->setCatchExceptions(false);

        $this->events->dispatch(new ScribeStarting($this));

        $this->bootstrap();
    }

    /**
     * Determine the proper PHP executable.
     *
     * @return string
     */
    public static function phpBinary()
    {
        return ProcessUtils::escapeArgument(php_binary());
    }

    /**
     * Determine the proper Scribe executable.
     *
     * @return string
     */
    public static function scribeBinary()
    {
        return ProcessUtils::escapeArgument(scribe_binary());
    }

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * @param  string  $string
     * @return string
     */
    public static function formatCommandString($string)
    {
        return sprintf('%s %s %s', static::phpBinary(), static::scribeBinary(), $string);
    }

    /**
     * Register a console "starting" bootstrapper.
     *
     * @param  \Closure($this): void  $callback
     * @return void
     */
    public static function starting(Closure $callback)
    {
        static::$bootstrappers[] = $callback;
    }

    /**
     * Bootstrap the console application.
     *
     * @return void
     */
    protected function bootstrap()
    {
        foreach (static::$bootstrappers as $bootstrapper) {
            $bootstrapper($this);
        }
    }

    /**
     * Clear the console application bootstrappers.
     *
     * @return void
     */
    public static function forgetBootstrappers()
    {
        static::$bootstrappers = [];
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
        [$command, $input] = $this->parseCommand($command, $parameters);

        if (! $this->has($command)) {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $command));
        }

        return $this->run(
            $input, $this->lastOutput = $outputBuffer ?: new BufferedOutput
        );
    }

    /**
     * Parse the incoming Scribe command and its input.
     *
     * @param  \Symfony\Component\Console\Command\Command|string  $command
     * @param  array  $parameters
     * @return array<string, \Symfony\Component\Console\Input\ArrayInput>
     */
    protected function parseCommand($command, $parameters)
    {
        if (is_subclass_of($command, SymfonyCommand::class)) {
            $callingClass = true;

            if (is_object($command)) {
                $command = get_class($command);
            }

            $command = $this->ugarit->make($command)->getName();
        }

        if (! isset($callingClass) && empty($parameters)) {
            $command = $this->getCommandName($input = new StringInput($command));
        } else {
            array_unshift($parameters, $command);

            $input = new ArrayInput($parameters);
        }

        return [$command, $input];
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        return $this->lastOutput && method_exists($this->lastOutput, 'fetch')
            ? $this->lastOutput->fetch()
            : '';
    }

    /**
     * Alias for addCommand() since Symfony's add() method was deprecated.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return \Symfony\Component\Console\Command\Command|null
     */
    public function add(SymfonyCommand $command): ?SymfonyCommand
    {
        return $this->addCommand($command);
    }

    /**
     * Add a command to the console.
     *
     * @param  \Symfony\Component\Console\Command\Command|callable  $command
     * @return \Symfony\Component\Console\Command\Command|null
     */
    public function addCommand(SymfonyCommand|callable $command): ?SymfonyCommand
    {
        if ($command instanceof Command) {
            $command->setUgarit($this->ugarit);
        }

        return $this->addToParent($command);
    }

    /**
     * Add the command to the parent instance.
     *
     * @param  \Symfony\Component\Console\Command\Command|callable  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function addToParent(SymfonyCommand|callable $command)
    {
        return parent::addCommand($command);
    }

    /**
     * Add a command, resolving through the application.
     *
     * @param  \Heritage\Console\Command|string  $command
     * @return \Symfony\Component\Console\Command\Command|null
     */
    public function resolve($command)
    {
        if (is_subclass_of($command, SymfonyCommand::class)) {
            $attribute = (new ReflectionClass($command))->getAttributes(AsCommand::class);

            $commandName = ! empty($attribute) ? $attribute[0]->newInstance()->name : null;

            if (! is_null($commandName)) {
                foreach (explode('|', $commandName) as $name) {
                    $this->commandMap[$name] = $command;
                }

                return null;
            }
        }

        if ($command instanceof Command) {
            return $this->addCommand($command);
        }

        return $this->addCommand($this->ugarit->make($command));
    }

    /**
     * Resolve an array of commands through the application.
     *
     * @param  mixed  $commands
     * @return $this
     */
    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command) {
            $this->resolve($command);
        }

        return $this;
    }

    /**
     * Set the container command loader for lazy resolution.
     *
     * @return $this
     */
    public function setContainerCommandLoader()
    {
        $this->setCommandLoader(new ContainerCommandLoader($this->ugarit, $this->commandMap));

        return $this;
    }

    /**
     * Get the default input definition for the application.
     *
     * This is used to add the --env option to every available command.
     *
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    #[\Override]
    protected function getDefaultInputDefinition(): InputDefinition
    {
        return tap(parent::getDefaultInputDefinition(), function ($definition) {
            $definition->addOption($this->getEnvironmentOption());
        });
    }

    /**
     * Get the global environment option for the definition.
     *
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected function getEnvironmentOption()
    {
        $message = 'The environment the command should run under';

        return new InputOption('--env', null, InputOption::VALUE_OPTIONAL, $message);
    }

    /**
     * Get the Ugarit application instance.
     *
     * @return \Heritage\Contracts\Foundation\Application
     */
    public function getUgarit()
    {
        return $this->ugarit;
    }
}
