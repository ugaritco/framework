<?php

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Foundation\Console\Concerns\InspectsModel;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:resource')]
class ResourceMakeCommand extends GeneratorCommand
{
    use InspectsModel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:resource
                    {name : The name of the resource}
                    {--f|force : Create the class even if the resource already exists}
                    {--j|json-api : Create a JSON:API resource}
                    {--c|collection : Create a resource collection}
                    {--m|model= : The model the resource represents; its columns become the fields}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->collection()) {
            $this->type = 'Resource collection';
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
        return match (true) {
            $this->collection() => $this->resolveStubPath('/stubs/resource-collection.stub'),
            $this->option('json-api') => $this->resolveStubPath('/stubs/resource-json-api.stub'),
            $this->option('model') !== null => $this->resolveStubPath('/stubs/resource.model.stub'),
            default => $this->resolveStubPath('/stubs/resource.stub'),
        };
    }

    /**
     * Build the class, listing the model's id, columns, translatable attributes and timestamps
     * as the resource fields when --model is given; a translatable model also returns its
     * loaded translations keyed by locale code.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Heritage\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $class = parent::buildClass($name);

        if (($model = $this->modelClass()) === null) {
            return $class;
        }

        $fields = ['id', ...array_keys($this->modelColumns($model)), ...$this->modelTranslatable($model), 'created_at', 'updated_at'];
        $lines = array_map(fn ($field) => "            '{$field}' => \$this->{$field},", array_unique($fields));

        if ($this->modelTranslatable($model) !== []) {
            $lines[] = "            'translations' => \$this->whenLoaded('translations', fn () => (object) \$this->translationsToArray()),";
        }

        return str_replace(
            ['{{ model }}', '{{ fields }}'],
            [class_basename($model), implode("\n", $lines)],
            $class
        );
    }

    /**
     * Place a resource given only by its name in a folder named after its model
     * (Http\Resources\{Model}\{Name}).
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = str_replace('/', '\\', ltrim($name, '\\/'));
        $model = $this->option('model');

        if ($model && ! str_contains($name, '\\')) {
            $name = class_basename(str_replace('/', '\\', $model)).'\\'.$name;
        }

        return parent::qualifyClass($name);
    }

    /**
     * Determine if the command is generating a resource collection.
     *
     * @return bool
     */
    protected function collection()
    {
        return $this->option('collection') ||
               str_ends_with($this->argument('name'), 'Collection');
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
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Resources';
    }
}
