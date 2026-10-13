<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:result')]
class ResultMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:result
                    {name : The name of the result enum}
                    {--artifact= : The target modular artifact name}
                    {--feedback= : The base translation key under responses.* (guessed from the action verb when omitted)}
                    {--f|force : Create the enum even if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new result enum resolving its feedback by convention';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Result';

    /**
     * The feedback key guessed from the leading verb of the result name.
     *
     * @var array<string, string>
     */
    protected array $verbFeedback = [
        'Create' => 'created',
        'Store' => 'stored',
        'Save' => 'saved',
        'Update' => 'updated',
        'Edit' => 'edited',
        'Show' => 'showed',
        'Index' => 'indexed',
        'Destroy' => 'deleted',
        'Delete' => 'deleted',
    ];

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/result.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->ugarit->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        if (! str_ends_with($name, 'Result')) {
            $name .= 'Result';
        }

        return parent::qualifyClass($name);
    }

    /**
     * Build the enum, filling its FEEDBACK constant.
     *
     * @param  string  $name  The fully-qualified enum name.
     * @return string The generated source.
     */
    protected function buildClass($name)
    {
        return str_replace('{{ feedback }}', $this->feedback($name), parent::buildClass($name));
    }

    /**
     * The FEEDBACK key: the --feedback option, or a guess from the action verb of the name
     * ("translation." is prefixed for translation results).
     *
     * @param  string  $name  The fully-qualified enum name.
     * @return string
     */
    protected function feedback(string $name): string
    {
        if ($feedback = $this->option('feedback')) {
            return (string) $feedback;
        }

        $class = class_basename($name);
        $verb = Str::before(Str::snake($class), '_');
        $feedback = $this->verbFeedback[Str::studly($verb)] ?? null;

        if ($feedback === null) {
            $this->components->warn("Could not guess the feedback key of [{$class}]; using \"saved\". Pass --feedback to choose it.");

            $feedback = 'saved';
        }

        return str_contains($class, 'Translation') ? "translation.{$feedback}" : $feedback;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Enums';
    }
}
