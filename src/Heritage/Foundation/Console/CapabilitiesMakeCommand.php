<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Foundation\Console\Concerns\InspectsModel;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Generates the full capabilities slice of a model: its repository, policy, results, parcels,
 * outcome, capabilities (index, create, show, edit, save, destroy), form requests, resource,
 * controller and lang file, each by its own make: command.
 */
#[AsCommand(name: 'make:capabilities')]
class CapabilitiesMakeCommand extends GeneratorCommand
{
    use InspectsModel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:capabilities
                    {name : The model the slice serves (e.g. Department)}
                    {--artifact= : The target modular artifact name}
                    {--routes : Register the Route::resource() in routes/web.php (the artifact\'s, or the application\'s)}
                    {--f|force : Overwrite the classes that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the capabilities slice of a model, from its repository to its controller';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Capabilities';

    /**
     * The operations of the slice: the ability each one requires and its feedback key.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    protected const OPERATIONS = [
        'Index' => ['viewAny', 'indexed'],
        'Create' => ['create', 'created'],
        'Show' => ['view', 'showed'],
        'Edit' => ['update', 'edited'],
        'Save' => ['save', 'saved'],
        'Destroy' => ['delete', 'deleted'],
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->promptForArtifact('Where should the capabilities be created?');

        $model = Str::studly(class_basename(str_replace('/', '\\', $this->getNameInput())));
        $modelClass = $this->qualifyModel($model);

        if (! class_exists($modelClass)) {
            $this->components->warn("[{$modelClass}] does not exist yet; the parcels, rules and resource fields stay empty until its \$fillable does.");
        }

        $this->makeFoundations($model);
        $this->makeCapabilities($model);
        $this->makeHttp($model);
        $this->makeLang($model, $modelClass);

        $routes = $this->routes($model);

        if ($this->option('routes')) {
            $this->registerRoutes($routes);

            return self::SUCCESS;
        }

        $this->components->info("The {$model} capabilities are ready. Register their routes:");

        foreach ($routes as $alias => [$controller, $route]) {
            $this->line('    '.str_replace("{$alias}::class", "\\{$controller}::class", $route));
        }

        return self::SUCCESS;
    }

    /**
     * The routes of the slice, keyed by the alias of their controller.
     *
     * @param  string  $model
     * @return array<string, array{0: class-string, 1: string}> Alias => [controller, route].
     */
    protected function routes(string $model): array
    {
        $controllers = trim($this->rootNamespace(), '\\')."\\Http\\Controllers\\{$model}\\Capabilities";

        return [
            "{$model}CapabilitiesController" => [
                "{$controllers}\\{$model}Controller",
                "Route::resource('".Str::kebab($model)."', {$model}CapabilitiesController::class);",
            ],
        ];
    }

    /**
     * Register the slice's routes in routes/web.php: inside its last group, or in a new file
     * grouping them under the web and auth middleware.
     *
     * @param  array<string, array{0: class-string, 1: string}>  $routes  Alias => [controller, route].
     * @return void
     */
    protected function registerRoutes(array $routes): void
    {
        $artifact = $this->getArtifactOption();
        $path = $artifact ? $this->ugarit->basePath("artifacts/{$artifact}/routes/web.php") : $this->ugarit->basePath('routes/web.php');

        if (! $this->files->exists($path)) {
            $imports = implode("\n", array_map(fn (string $alias) => "use {$routes[$alias][0]} as {$alias};", array_keys($routes)));
            $lines = implode("\n\n", array_map(fn (array $route) => "    {$route[1]}", $routes));
            $group = $artifact ? "Route::middleware(['web', 'auth'])->prefix('panel/artifact/{$artifact}')" : "Route::middleware(['web', 'auth'])";

            $this->makeDirectory($path);
            $this->files->put($path, "<?php\n\nuse Heritage\\Support\\Facades\\Route;\n{$imports}\n\n{$group}->group(function () {\n{$lines}\n});\n");
            $this->components->info(sprintf('Routes [%s] created with [%s].', $path, implode('], [', array_column($routes, 1))));

            return;
        }

        $content = $this->files->get($path);

        foreach ($routes as $alias => [$controller, $route]) {
            if (str_contains($content, Str::before($route, ',').',')) {
                $this->components->info(sprintf('Routes [%s] already register [%s].', $path, Str::betweenFirst($route, "'", "'")));

                continue;
            }

            $content = $this->insertRoute($content, "use {$controller} as {$alias};", $route);
            $this->components->info(sprintf('Routes [%s] register [%s].', $path, $route));
        }

        $this->files->put($path, $content);
    }

    /**
     * Insert a route at the end of the last group of a routes file, and its controller import
     * after the last one.
     *
     * @param  string  $content  The routes file.
     * @param  string  $import  The "use" statement of the controller.
     * @param  string  $route
     * @return string
     */
    protected function insertRoute(string $content, string $import, string $route): string
    {
        $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";

        if (! str_contains($content, $import)) {
            if (preg_match_all('/^use [^;]+;/m', $content, $uses, PREG_OFFSET_CAPTURE)) {
                [$last, $offset] = end($uses[0]);
                $content = substr_replace($content, $eol.$import, $offset + strlen($last), 0);
            } else {
                $content = preg_replace('/^<\?php\r?\n/', '<?php'.$eol.$eol.$import.$eol, $content, 1);
            }
        }

        return preg_match_all('/^\}\);/m', $content, $closings, PREG_OFFSET_CAPTURE)
            ? substr_replace($content, "{$eol}    {$route}{$eol}", end($closings[0])[1], 0)
            : rtrim($content).$eol.$eol.$route.$eol;
    }

    /**
     * Generate what the capabilities rely on: the repository, policy, results, parcels and outcome.
     *
     * @param  string  $model
     * @return void
     */
    protected function makeFoundations(string $model): void
    {
        $this->generate('make:repository', ['name' => $model]);
        $this->generate('make:policy', ['name' => "{$model}Policy", '--model' => $model]);

        foreach (static::OPERATIONS as $operation => [, $feedback]) {
            $this->generate('make:result', [
                'name' => "{$model}/Capabilities/{$operation}{$this->subject($operation, $model)}Result",
                '--feedback' => $feedback,
            ]);
        }

        foreach (array_keys(static::OPERATIONS) as $operation) {
            $this->generate('make:parcel', [
                'name' => "{$model}/Capabilities/{$operation}{$model}Parcel",
                '--model' => $model,
                ...($operation === 'Index' ? ['--query' => true] : ['--operation' => strtolower($operation)]),
            ]);
        }

        $this->generate('make:outcome', ['name' => $model, '--result' => "{$model}/Capabilities/Save{$model}Result"]);
    }

    /**
     * Generate the capabilities, each authorized by its policy ability.
     *
     * @param  string  $model
     * @return void
     */
    protected function makeCapabilities(string $model): void
    {
        foreach (static::OPERATIONS as $operation => [$ability]) {
            $subject = $this->subject($operation, $model);

            $this->generate('make:capability', [
                'name' => "{$model}/{$operation}{$subject}Capability",
                '--operation' => strtolower($operation),
                '--parcel' => "{$model}/Capabilities/{$operation}{$model}Parcel",
                '--result' => "{$model}/Capabilities/{$operation}{$subject}Result",
                '--outcome' => "{$model}/{$model}Outcome",
                '--repository' => $operation === 'Create' ? null : "{$model}/{$model}RepositoryInterface",
                '--authorize' => $ability,
                '--model' => $model,
            ]);
        }
    }

    /**
     * Generate the HTTP side: the form requests, the resource and the controller.
     *
     * @param  string  $model
     * @return void
     */
    protected function makeHttp(string $model): void
    {
        foreach (['Store' => 'Save', 'Update' => 'Save', 'Destroy' => 'Destroy'] as $request => $capability) {
            $this->generate('make:request', [
                'name' => "{$request}{$model}Request",
                '--capability' => true,
                '--model' => $model,
                '--operation' => strtolower($request),
                '--action' => "Capabilities/{$model}/{$capability}{$model}Capability",
            ]);
        }

        $this->generate('make:resource', ['name' => "{$model}Resource", '--model' => $model]);
        $this->generate('make:controller', ['name' => "{$model}Controller", '--capabilities' => true, '--model' => $model]);
    }

    /**
     * Write the model's lang file (name, plural and the label of each field) unless it exists.
     *
     * @param  string  $model
     * @param  string  $modelClass
     * @return void
     */
    protected function makeLang(string $model, string $modelClass): void
    {
        $artifact = $this->getArtifactOption();
        $file = Str::snake($model).'.php';
        $path = $artifact ? $this->ugarit->basePath("artifacts/{$artifact}/lang/en/{$file}") : $this->ugarit->langPath("en/{$file}");

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->info(sprintf('Lang [%s] already exists.', $path));

            return;
        }

        $fields = [...array_keys($this->modelColumns($modelClass)), ...$this->modelTranslatable($modelClass), 'created_at', 'updated_at', 'force'];
        $attributes = array_map(function (string $field) {
            $label = Str::headline(preg_replace('/_id$/', '', $field));

            return "        '{$field}' => [\n            'label' => '{$label}',\n            'placeholder' => null,\n        ],";
        }, array_unique($fields));

        $label = str_replace('_', ' ', Str::snake($model));

        $this->makeDirectory($path);
        $this->files->put($path, str_replace(
            ['{{ label }}', '{{ name }}', '{{ plural }}', '{{ attributes }}'],
            [$label, Str::headline($model), Str::headline(Str::plural($model)), implode("\n\n", $attributes)],
            $this->files->get($this->getStub())
        ));

        $this->components->info(sprintf('Lang [%s] created successfully.', $path));
    }

    /**
     * Run a generator with the slice's artifact and --force.
     *
     * @param  string  $command
     * @param  array<string, mixed>  $arguments
     * @return void
     */
    protected function generate(string $command, array $arguments): void
    {
        $this->call($command, array_filter([
            ...$arguments,
            '--artifact' => $this->getArtifactOption(),
            '--force' => (bool) $this->option('force'),
        ], fn ($value) => $value !== null && $value !== false));
    }

    /**
     * The subject of an operation's class names: plural for the index, singular otherwise.
     *
     * @param  string  $operation
     * @param  string  $model
     * @return string
     */
    protected function subject(string $operation, string $model): string
    {
        return $operation === 'Index' ? Str::plural($model) : $model;
    }

    /**
     * Get the stub file of the lang file.
     *
     * @return string
     */
    protected function getStub()
    {
        return file_exists($customPath = $this->ugarit->basePath('stubs/lang.model.stub'))
            ? $customPath
            : __DIR__.'/stubs/lang.model.stub';
    }
}
