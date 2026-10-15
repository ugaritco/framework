<?php

namespace Heritage\Database\Console\Factories;

use Heritage\Console\GeneratorCommand;
use Heritage\Database\Console\Field;
use Heritage\Support\Str;
use Heritage\Support\Stringable;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'make:factory')]
class FactoryMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:factory
                    {name : The name of the factory}
                    {--m|model= : The name of the model}
                    {--artifact= : The target modular artifact name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Factory';

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->promptForArtifact('Which modular artifact should this factory belong to?');

        if (! $this->option('model') && $this->input->isInteractive()) {
            $this->promptForModel('Which model should this factory correspond to?');
        }

        return parent::handle();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/factory.stub');
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
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $factory = class_basename(Str::ucfirst(str_replace('Factory', '', $name)));

        $namespaceModel = $this->option('model')
            ? $this->qualifyModel($this->option('model'))
            : $this->qualifyModel($this->guessModelName($name));

        $model = class_basename($namespaceModel);

        $artifact = $this->getArtifactOption();

        if ($artifact) {
            $studly = Str::studly($artifact);
            $namespace = "Ugarit\\Artifacts\\{$studly}\\Database\\Factories";
        } else {
            $namespace = $this->getNamespace(
                Str::replaceFirst($this->rootNamespace(), 'Database\\Factories\\', $this->qualifyClass($this->getNameInput()))
            );
        }

        $replace = [
            '{{ factoryNamespace }}' => $namespace,
            'NamespacedDummyModel' => $namespaceModel,
            '{{ namespacedModel }}' => $namespaceModel,
            '{{namespacedModel}}' => $namespaceModel,
            'DummyModel' => $model,
            '{{ model }}' => $model,
            '{{model}}' => $model,
            '{{ factory }}' => $factory,
            '{{factory}}' => $factory,
        ];

        $class = str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );

        if ($this->guessedModel("{$namespace}\\{$factory}Factory") === $namespaceModel) {
            $class = preg_replace('/^    \/\*\*(?:(?!\*\/)[\s\S])*\*\/\s*protected \$model = [^;]+;\R\R?/m', '', $class, 1);
        }

        [$lines, $imports] = $this->definition($namespaceModel);

        if ($lines === []) {
            return $class;
        }

        $class = preg_replace('/^            \/\/(\r?\n)/m', implode("\n", $lines).'$1', $class, 1);

        return str_replace("use {$namespaceModel};", implode("\n", array_map(fn ($import) => "use {$import};", [$namespaceModel, ...$imports])), $class);
    }

    /**
     * The default state of an existing model: a fake value for each fillable and translatable
     * attribute, guessed from its name and cast; a foreign key creates its related model.
     *
     * @param  string  $model
     * @return array{0: array<int, string>, 1: array<int, string>} The definition lines and the classes they import.
     */
    protected function definition(string $model): array
    {
        if (! class_exists($model)) {
            return [[], []];
        }

        try {
            $instance = new $model;
        } catch (Throwable) {
            return [[], []];
        }

        $casts = $instance->getCasts();
        $translatable = method_exists($instance, 'getTranslatable') ? $instance->getTranslatable() : [];
        $lines = [];
        $imports = [];

        foreach (array_unique([...$instance->getFillable(), ...$translatable]) as $column) {
            $value = $this->fakeValue($column, $casts[$column] ?? null);

            if (str_ends_with($column, '_id') && class_exists($related = Str::beforeLast($model, '\\').'\\'.Str::studly(Str::beforeLast($column, '_id')))) {
                $imports[] = $related;
                $value = class_basename($related).'::factory()';
            }

            $lines[] = "            '{$column}' => {$value},";
        }

        return [$lines, array_unique($imports)];
    }

    /**
     * A fake value for an attribute, guessed from its name, then its cast.
     *
     * @param  string  $column
     * @param  string|null  $cast
     * @return string The PHP expression.
     */
    protected function fakeValue(string $column, ?string $cast): string
    {
        return Field::fake($column, $cast);
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = (new Stringable($name))->replaceFirst($this->rootNamespace(), '')->finish('Factory')->value();

        if ($artifact = $this->getArtifactOption()) {
            return $this->ugarit->basePath("artifacts/{$artifact}/database/factories/").str_replace('\\', '/', $name).'.php';
        }

        return $this->ugarit->databasePath().'/factories/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * The model Factory::modelName() guesses for a factory without a $model property, so the
     * property is written only when it names another model.
     *
     * @param  string  $factory  The factory class, e.g. "Ugarit\Artifacts\Library\Database\Factories\BookFactory".
     * @return string e.g. "Ugarit\Artifacts\Library\Models\Book".
     */
    protected function guessedModel(string $factory): string
    {
        $model = Str::replaceLast('Factory', '', $factory);

        if (str_contains($model, '\\Database\\Factories\\')) {
            return str_replace('\\Database\\Factories\\', '\\Models\\', $model);
        }

        return $this->rootNamespace().'Models\\'.Str::after($model, 'Database\\Factories\\');
    }

    /**
     * Guess the model name from the Factory name or return a default model name.
     *
     * @param  string  $name
     * @return string
     */
    protected function guessModelName($name)
    {
        if (str_ends_with($name, 'Factory')) {
            $name = substr($name, 0, -7);
        }

        $modelName = $this->qualifyModel(Str::after($name, $this->rootNamespace()));

        if (class_exists($modelName)) {
            return $modelName;
        }

        if ($this->getArtifactOption()) {
            return $this->rootNamespace().'Models\\'.class_basename($name);
        }

        if (is_dir(app_path('Models/'))) {
            return $this->rootNamespace().'Models\Model';
        }

        return $this->rootNamespace().'Model';
    }
}
