<?php

namespace Heritage\Console;

use Heritage\Console\Attributes\Aliases;
use Heritage\Console\Attributes\Description;
use Heritage\Console\Attributes\Help;
use Heritage\Console\Attributes\Hidden;
use Heritage\Console\Attributes\Signature;
use Heritage\Console\Attributes\Usage;
use Heritage\Console\View\Components\Factory;
use Heritage\Contracts\Console\Isolatable;
use Heritage\Support\Traits\Macroable;
use ReflectionClass;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Command extends SymfonyCommand
{
    use Concerns\CallsCommands,
        Concerns\ConfiguresPrompts,
        Concerns\HasParameters,
        Concerns\InteractsWithIO,
        Concerns\InteractsWithSignals,
        Concerns\PromptsForMissingInput,
        Macroable;

    /**
     * The Ugarit application instance.
     *
     * @var \Heritage\Contracts\Foundation\Application
     */
    protected $ugarit;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * The console command help text.
     *
     * @var string
     */
    protected $help = '';

    /**
     * Indicates whether the command should be shown in the Scribe command list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Indicates whether only one instance of the command can run at any given time.
     *
     * @var bool
     */
    protected $isolated = false;

    /**
     * The default exit code for isolated commands.
     *
     * @var self::SUCCESS|self::FAILURE|self::INVALID
     */
    protected $isolatedExitCode = self::SUCCESS;

    /**
     * The console command name aliases.
     *
     * @var string[]
     */
    protected $aliases;

    /**
     * Create a new console command instance.
     */
    public function __construct()
    {
        $this->configureFromAttributes();

        // We will go ahead and set the name, description, and parameters on console
        // commands just to make things a little easier on the developer. This is
        // so they don't have to all be manually specified in the constructors.
        if (isset($this->signature)) {
            $this->configureUsingFluentDefinition();
        } else {
            parent::__construct($this->name);
        }

        $this->configureUsageFromAttribute();

        // Once we have constructed the command, we'll set the description and other
        // related properties of the command. If a signature wasn't used to build
        // the command we'll set the arguments and the options on this command.
        if (! empty($this->description)) {
            $this->setDescription($this->description);
        }

        if (! empty($this->help)) {
            $this->setHelp($this->help);
        }

        $this->setHidden($this->isHidden());

        if (isset($this->aliases)) {
            $this->setAliases((array) $this->aliases);
        }

        if (! isset($this->signature)) {
            $this->specifyParameters();
        }

        $this->configureDefaults();

        if ($this instanceof Isolatable) {
            $this->configureIsolation();
        }
    }

    /**
     * Configure argument/option defaults that can't be expressed as static signature
     * text (e.g. environment-dependent values, or non-string literal defaults such as
     * booleans or integers), by patching the already-built definition.
     */
    protected function configureDefaults(): void
    {
        //
    }

    /**
     * Configure the command from class attributes.
     *
     * @return void
     */
    protected function configureFromAttributes()
    {
        $reflection = new ReflectionClass($this);

        $signature = $reflection->getAttributes(Signature::class);

        if ($signature !== []) {
            $signatureInstance = $signature[0]->newInstance();

            $this->signature = $signatureInstance->signature;

            if ($signatureInstance->aliases !== null) {
                $this->aliases = $signatureInstance->aliases;
            }
        }

        $description = $reflection->getAttributes(Description::class);

        if ($description !== []) {
            $this->description = $description[0]->newInstance()->description;
        }

        $help = $reflection->getAttributes(Help::class);

        if ($help !== []) {
            $this->help = $help[0]->newInstance()->help;
        }

        if ($reflection->getAttributes(Hidden::class) !== []) {
            $this->hidden = true;
        }

        $aliases = $reflection->getAttributes(Aliases::class);

        if ($aliases !== []) {
            $this->aliases = $aliases[0]->newInstance()->aliases;
        }
    }

    /**
     * Configure usage examples for the command from class attributes.
     *
     * @return void
     */
    protected function configureUsageFromAttribute()
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getAttributes(Usage::class) as $usage) {
            $this->addUsage($usage->newInstance()->usage);
        }
    }

    /**
     * Configure the console command using a fluent definition.
     *
     * @return void
     */
    protected function configureUsingFluentDefinition()
    {
        [$name, $arguments, $options] = Parser::parse($this->signature);

        parent::__construct($this->name = $name);

        // After parsing the signature we will spin through the arguments and options
        // and set them on this command. These will already be changed into proper
        // instances of these "InputArgument" and "InputOption" Symfony classes.
        $this->getDefinition()->addArguments($arguments);
        $this->getDefinition()->addOptions($options);
    }

    /**
     * Configure the console command for isolation.
     *
     * @return void
     */
    protected function configureIsolation()
    {
        $this->getDefinition()->addOption(new InputOption(
            'isolated',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not run the command if another instance of the command is already running',
            $this->isolated
        ));
    }

    /**
     * Run the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    #[\Override]
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output instanceof OutputStyle ? $output : $this->ugarit->make(
            OutputStyle::class, ['input' => $input, 'output' => $output]
        );

        $this->components = $this->ugarit->make(Factory::class, ['output' => $this->output]);

        $this->configurePrompts($input);

        try {
            return parent::run(
                $this->input = $input, $this->output
            );
        } finally {
            $this->untrap();
        }
    }

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this instanceof Isolatable && $this->option('isolated') !== false &&
            ! $this->commandIsolationMutex()->create($this)) {
            $this->comment(sprintf(
                'The [%s] command is already running.', $this->getName()
            ));

            return (int) (is_numeric($this->option('isolated'))
                ? $this->option('isolated')
                : $this->isolatedExitCode);
        }

        $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

        try {
            return (int) $this->ugarit->call([$this, $method]);
        } catch (ManuallyFailedException $e) {
            $this->components->error($e->getMessage());

            return static::FAILURE;
        } finally {
            if ($this instanceof Isolatable && $this->option('isolated') !== false) {
                $this->commandIsolationMutex()->forget($this);
            }
        }
    }

    /**
     * Get a command isolation mutex instance for the command.
     *
     * @return \Heritage\Console\CommandMutex
     */
    protected function commandIsolationMutex()
    {
        return $this->ugarit->bound(CommandMutex::class)
            ? $this->ugarit->make(CommandMutex::class)
            : $this->ugarit->make(CacheCommandMutex::class);
    }

    /**
     * Resolve the console command instance for the given command.
     *
     * @param  \Symfony\Component\Console\Command\Command|string  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function resolveCommand($command)
    {
        if (is_string($command)) {
            if (! class_exists($command)) {
                return $this->getApplication()->find($command);
            }

            $command = $this->ugarit->make($command);
        }

        if ($command instanceof SymfonyCommand) {
            $command->setApplication($this->getApplication());
        }

        if ($command instanceof self) {
            $command->setUgarit($this->getUgarit());
        }

        return $command;
    }

    /**
     * Fail the command manually.
     *
     * @param  \Throwable|string|null  $exception
     * @return never
     *
     * @throws \Heritage\Console\ManuallyFailedException|\Throwable
     */
    public function fail(Throwable|string|null $exception = null)
    {
        if (is_null($exception)) {
            $exception = 'Command failed manually.';
        }

        if (is_string($exception)) {
            $exception = new ManuallyFailedException($exception);
        }

        throw $exception;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    #[\Override]
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function setHidden(bool $hidden = true): static
    {
        parent::setHidden($this->hidden = $hidden);

        return $this;
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

    /**
     * Set the Ugarit application instance.
     *
     * @param  \Heritage\Contracts\Container\Container  $ugarit
     * @return void
     */
    public function setUgarit($ugarit)
    {
        $this->ugarit = $ugarit;
    }
}
