<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Generates the repository of a model: its contract under Contracts/Repositories/{Model} and its
 * ModelRepository implementation under Repositories/{Model}, which the container binds by convention.
 */
#[AsCommand(name: 'make:repository')]
class RepositoryMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository
                    {name : The model the repository serves (e.g. Department)}
                    {--artifact= : The target modular artifact name}
                    {--model= : The model class when it differs from the name (relative to Models/ or fully-qualified)}
                    {--f|force : Create the classes even if they already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a repository contract and its implementation for a model';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * Generate the implementation, then its contract.
     *
     * @return bool|null
     */
    public function handle()
    {
        if (parent::handle() === false) {
            return false;
        }

        $contract = $this->contractClass();
        $path = $this->getPath($contract);

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->warn("Repository contract [{$path}] already exists.");

            return null;
        }

        $this->makeDirectory($path);
        $this->files->put($path, $this->sortImports($this->fill(
            $this->files->get($this->resolveStubPath('/stubs/repository.interface.stub')),
            $contract
        )));

        $this->components->info(sprintf('Repository contract [%s] created successfully.', windows_os() ? str_replace('/', '\\', $path) : $path));

        return null;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/repository.stub');
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
     * The implementation class: Repositories\{Model}\{Model}Repository.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $model = $this->modelName();

        return $this->rootNamespace()."Repositories\\{$model}\\{$model}Repository";
    }

    /**
     * The contract class: Contracts\Repositories\{Model}\{Model}RepositoryInterface.
     *
     * @return string
     */
    protected function contractClass(): string
    {
        $model = $this->modelName();

        return $this->rootNamespace()."Contracts\\Repositories\\{$model}\\{$model}RepositoryInterface";
    }

    /**
     * The model's base name, from the name argument without a "Repository" suffix.
     *
     * @return string
     */
    protected function modelName(): string
    {
        return Str::studly(preg_replace('/Repository(Interface)?$/', '', class_basename(str_replace('/', '\\', $this->getNameInput()))));
    }

    /**
     * The model class the repository serves.
     *
     * @return string
     */
    protected function namespacedModel(): string
    {
        $model = $this->option('model') ?: $this->modelName();
        $model = str_replace('/', '\\', trim((string) $model, '\\/'));

        return class_exists($model) ? $model : $this->qualifyModel($model);
    }

    /**
     * Build the implementation.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        return $this->fill(parent::buildClass($name), $name);
    }

    /**
     * Fill the namespace, class, model and companion placeholders of a stub.
     *
     * @param  string  $stub
     * @param  string  $name  The class being written.
     * @return string
     */
    protected function fill(string $stub, string $name): string
    {
        $this->replaceNamespace($stub, $name);
        $model = $this->namespacedModel();

        return str_replace(
            ['{{ namespacedContract }}', '{{ contract }}', '{{ implementation }}', '{{ namespacedModel }}', '{{ model }}'],
            [$this->contractClass(), class_basename($this->contractClass()), class_basename($this->qualifyClass('')), $model, class_basename($model)],
            $this->replaceClass($stub, $name)
        );
    }
}
