<?php

namespace Heritage\Foundation\Console;

use Heritage\Console\Concerns\CreatesMatchingTest;
use Heritage\Console\GeneratorCommand;
use Heritage\Foundation\Console\Ui\Blueprint;
use Heritage\Foundation\Console\Ui\Concerns\GeneratesUi;
use Heritage\Foundation\Inspiring;
use Heritage\Support\Collection;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:component')]
class ComponentMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest, GeneratesUi;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:component';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a component of the frontend stack, or a view component class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Component';

    /**
     * Execute the console command: a component of the frontend stack when one is used (or a type
     * is given), else, or with --stack=blade, --view or --inline, a Blade view component.
     *
     * @return int|bool|null
     */
    public function handle()
    {
        $blade = $this->option('stack') === 'blade' || $this->option('view') || $this->option('inline');

        if (! $blade && ($this->option('type') !== null || $this->detectStack() !== null)) {
            return $this->writeUiComponent();
        }

        if ($this->option('view')) {
            return $this->writeView();
        }

        if (parent::handle() === false && ! $this->option('force')) {
            return;
        }

        if (! $this->option('inline')) {
            $this->writeView();
        }
    }

    /**
     * Write a component of the frontend stack: in the artifact's panel module with --artifact (in
     * its model's partials with --model), else in resources/js/components.
     *
     * @return int
     */
    protected function writeUiComponent(): int
    {
        if (! $stack = $this->stack()) {
            return self::FAILURE;
        }

        $type = (string) ($this->option('type') ?: 'blank');
        $types = $stack->componentTypes();
        $artifact = $this->getArtifactOption();
        $model = $this->option('model') ? Str::studly((string) $this->option('model')) : null;

        if (! isset($types[$type])) {
            $this->components->error("Unknown component type [{$type}]; choose among: ".implode(', ', array_keys($types)).'.');

            return self::FAILURE;
        }

        if ($types[$type] && $model === null) {
            $this->components->error("A [{$type}] component presents a model; give it with --model.");

            return self::FAILURE;
        }

        if ($model !== null && $artifact === null) {
            $this->components->error('Give the artifact of the model with --artifact.');

            return self::FAILURE;
        }

        $blueprint = null;

        if ($model !== null) {
            if (! class_exists(Blueprint::modelClass($artifact, $model))) {
                $this->components->error('The model ['.Blueprint::modelClass($artifact, $model)."] does not exist; create it with make:model {$model} --artifact={$artifact} --all.");

                return self::FAILURE;
            }

            $blueprint = Blueprint::read($this->files, $this->ugarit->basePath(), $artifact, $model);
        }

        $name = implode('/', array_map(fn (string $segment) => Str::studly($segment), explode('/', str_replace('\\', '/', (string) $this->argument('name')))));
        $directory = $blueprint ? 'pages/'.Str::kebab($model).'/partials' : 'components';
        [$root, $alias] = $artifact !== null ? [$this->modulePath($artifact), "@module/{$artifact}"] : [$this->ugarit->basePath('resources/js'), '@'];

        $this->writeFiles(
            $root,
            $stack->componentFiles($type, "{$directory}/{$name}", "{$alias}/{$directory}/{$name}", $blueprint),
            $blueprint ? $stack->pageReplacements($blueprint, []) : []
        );
        $this->printReport();

        return self::SUCCESS;
    }

    /**
     * Write the view for the component.
     *
     * @return void
     */
    protected function writeView()
    {
        $separator = '/';

        if (windows_os()) {
            $separator = '\\';
        }

        $path = $this->viewPath(
            str_replace('.', $separator, $this->getView()).'.blade.php'
        );

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error('View already exists.');

            return;
        }

        file_put_contents(
            $path,
            '<div>
    <!-- '.Inspiring::quotes()->random().' -->
</div>'
        );

        $this->components->info(sprintf('%s [%s] created successfully.', 'View', $path));
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        if ($this->option('inline')) {
            return str_replace(
                ['DummyView', '{{ view }}'],
                "<<<'blade'\n<div>\n    <!-- ".Inspiring::quotes()->random()." -->\n</div>\nblade",
                parent::buildClass($name)
            );
        }

        return str_replace(
            ['DummyView', '{{ view }}'],
            'view(\''.$this->getView().'\')',
            parent::buildClass($name)
        );
    }

    /**
     * Get the view name relative to the view path.
     *
     * @return string view
     */
    protected function getView()
    {
        $segments = explode('/', str_replace('\\', '/', $this->argument('name')));

        $name = array_pop($segments);

        $path = is_string($this->option('path'))
            ? explode('/', trim($this->option('path'), '/'))
            : [
                'components',
                ...$segments,
            ];

        $path[] = $name;

        return (new Collection($path))
            ->map(fn ($segment) => Str::kebab($segment))
            ->implode('.');
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/view-component.stub');
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
        return $rootNamespace.'\View\Components';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['inline', null, InputOption::VALUE_NONE, 'Create a component that renders an inline view'],
            ['view', null, InputOption::VALUE_NONE, 'Create an anonymous component with only a view'],
            ['path', null, InputOption::VALUE_REQUIRED, 'The location where the component view should be created'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the component already exists'],
            ['type', null, InputOption::VALUE_REQUIRED, 'The frontend component type: blank, dialog, form, table, select or summary'],
            ['model', null, InputOption::VALUE_REQUIRED, 'The model a frontend component presents (with --artifact)'],
            ['stack', null, InputOption::VALUE_REQUIRED, 'The frontend stack (vue), or blade for a view component; detected from package.json by default'],
        ];
    }
}
