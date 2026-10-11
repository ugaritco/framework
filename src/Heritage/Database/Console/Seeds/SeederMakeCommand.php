<?php

namespace Heritage\Database\Console\Seeds;

use Heritage\Console\GeneratorCommand;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:seeder')]
class SeederMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:seeder
                    {name : The name of the seeder}
                    {--m|model= : The name of the model}
                    {--artifact= : The target modular artifact name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new seeder class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Seeder';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->promptForArtifact('Which modular artifact should this seeder belong to?');

        if (! $this->option('model') && $this->input->isInteractive()) {
            $this->promptForModel('Which model should this seeder correspond to? (optional)');
        }

        parent::handle();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('model')) {
            return $this->resolveStubPath('/stubs/seeder.model.stub');
        }

        return $this->resolveStubPath('/stubs/seeder.stub');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        if ($this->option('model')) {
            $namespaceModel = $this->qualifyModel($this->option('model'));
            $model = class_basename($namespaceModel);

            $replace = [
                'NamespacedDummyModel' => $namespaceModel,
                '{{ namespacedModel }}' => $namespaceModel,
                '{{namespacedModel}}' => $namespaceModel,
                'DummyModel' => $model,
                '{{ model }}' => $model,
                '{{model}}' => $model,
            ];

            return str_replace(array_keys($replace), array_values($replace), $stub);
        }

        return $stub;
    }

    /**
     * Qualify the given model class name.
     *
     * @param  string  $model
     * @return string
     */
    protected function qualifyModel(string $model)
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        if ($artifact = $this->getArtifactOption()) {
            $studly = Str::studly($artifact);

            if (Str::startsWith($model, "Ugarit\\Artifacts\\{$studly}\\Models\\")) {
                return $model;
            }

            return "Ugarit\\Artifacts\\{$studly}\\Models\\{$model}";
        }

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $this->ugarit->getNamespace().'Models\\'.$model
            : $this->ugarit->getNamespace().$model;
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return is_file($customPath = $this->ugarit->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name));

        if ($artifact = $this->getArtifactOption()) {
            return $this->ugarit->basePath("artifacts/{$artifact}/database/seeders/").$name.'.php';
        }

        if (is_dir($this->ugarit->databasePath().'/seeds')) {
            return $this->ugarit->databasePath().'/seeds/'.$name.'.php';
        }

        return $this->ugarit->databasePath().'/seeders/'.$name.'.php';
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        if ($artifact = $this->getArtifactOption()) {
            $studly = Str::studly($artifact);

            return "Ugarit\\Artifacts\\{$studly}\\Database\\Seeders\\";
        }

        return 'Database\Seeders\\';
    }
}
