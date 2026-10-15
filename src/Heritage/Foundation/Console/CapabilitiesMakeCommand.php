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

        $translatable = $this->modelTranslatable($modelClass) !== [];

        $this->makeFoundations($model);
        $this->makeCapabilities($model);
        $this->makeHttp($model);
        $this->makeLang($model, $modelClass);

        if ($translatable) {
            $this->makeTranslation($model, $modelClass);
        }

        $routes = $this->routes($model, $translatable);

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
     * The routes of the slice, keyed by the alias of their controller: the resource, and the
     * translations nested under it for a translatable model.
     *
     * @param  string  $model
     * @param  bool  $translatable
     * @return array<string, array{0: class-string, 1: string}> Alias => [controller, route].
     */
    protected function routes(string $model, bool $translatable): array
    {
        $controllers = trim($this->rootNamespace(), '\\')."\\Http\\Controllers\\{$model}\\Capabilities";
        $kebab = Str::kebab($model);
        $routes = [
            "{$model}CapabilitiesController" => [
                "{$controllers}\\{$model}Controller",
                "Route::resource('{$kebab}', {$model}CapabilitiesController::class);",
            ],
        ];

        if ($translatable) {
            $parameter = Str::snake($model);
            $routes["{$model}TranslationCapabilitiesController"] = [
                "{$controllers}\\{$model}TranslationController",
                "Route::resource('{{$parameter}}/{$kebab}-translation', {$model}TranslationCapabilitiesController::class)"
                ."->only(['store', 'update', 'destroy'])->scoped(['{$parameter}_translation' => 'id']);",
            ];
        }

        return $routes;
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
     * Generate the translations sub-resource of a translatable model: its results, repository,
     * parcels, capabilities (authorized by the "translate" ability), form requests, resource and
     * controller, each translation addressed by its locale code.
     *
     * @param  string  $model
     * @param  class-string  $modelClass
     * @return void
     */
    protected function makeTranslation(string $model, string $modelClass): void
    {
        foreach (['Save' => 'translation.saved', 'Destroy' => 'translation.deleted'] as $operation => $feedback) {
            $this->generate('make:result', [
                'name' => "{$model}/Capabilities/Translation/{$operation}{$model}TranslationResult",
                '--feedback' => $feedback,
            ]);
        }

        $instance = new $modelClass;
        $translatable = $this->modelTranslatable($modelClass);
        $foreignKey = $instance->getTranslationForeignKey();
        $translationClass = $instance->getTranslationModel();
        $rules = $this->translationRules($model, $instance->getTable(), $translatable);
        $unique = array_keys(array_filter($rules, fn (array $rule) => $rule['unique']));
        $root = trim($this->rootNamespace(), '\\');
        $confirm = array_values(array_intersect(['name', 'title'], $translatable))[0] ?? $translatable[0];

        $replace = [
            '{{ rootNamespace }}' => $root,
            '{{ model }}' => $model,
            '{{ modelVariable }}' => Str::camel($model),
            '{{ label }}' => Str::lower(Str::headline($model)),
            '{{ foreignKey }}' => $foreignKey,
            '{{ routeParameter }}' => Str::snake($model),
            '{{ translationParameter }}' => Str::snake($model).'_translation',
            '{{ translationClass }}' => $translationClass,
            '{{ translationName }}' => class_basename($translationClass),
            '{{ translationVariable }}' => Str::camel($model).'Translation',
            '{{ confirm }}' => $confirm,
            '{{ confirmLabel }}' => str_replace('_', ' ', $confirm),
            '{{ fieldDocs }}' => implode("\n", array_map(fn (string $field) => "     * @param  string|null  \${$field}  Translated ".str_replace('_', ' ', $field).'.', $translatable)),
            '{{ fieldParameters }}' => implode("\n", array_map(fn (string $field) => "        public ?string \${$field} = null,", $translatable)),
            '{{ fields }}' => implode("\n", array_map(fn (string $field) => "            '{$field}' => \$this->{$field},", $translatable)),
        ];

        $saveCapability = "{$root}\\Capabilities\\{$model}\\Translation\\Save{$model}TranslationCapability";
        $files = [
            'repository.interface' => ["Contracts\\Repositories\\{$model}\\{$model}TranslationRepositoryInterface", []],
            'repository' => ["Repositories\\{$model}\\{$model}TranslationRepository", []],
            'parcel.save' => ["Parcels\\{$model}\\Capabilities\\Translation\\Save{$model}TranslationParcel", []],
            'parcel.destroy' => ["Parcels\\{$model}\\Capabilities\\Translation\\Destroy{$model}TranslationParcel", []],
            'capability.save' => ["Capabilities\\{$model}\\Translation\\Save{$model}TranslationCapability", []],
            'capability.destroy' => ["Capabilities\\{$model}\\Translation\\Destroy{$model}TranslationCapability", []],
            'request.store' => ["Http\\Requests\\{$model}\\Capabilities\\Translation\\Store{$model}TranslationRequest", [
                '{{ imports }}' => $this->renderUses([
                    'Closure', 'Heritage\\Foundation\\Http\\Attributes\\ForAction', 'Heritage\\Foundation\\Http\\FormRequest',
                    'Heritage\\Translation\\Locales', 'Heritage\\Validation\\Rule', $saveCapability, $modelClass,
                    ...($unique !== [] ? ['Heritage\\Validation\\Rules\\UniqueTranslation'] : []),
                ]),
                '{{ rules }}' => $this->renderTranslationRules($model, $rules, 'store', $foreignKey),
            ]],
            'request.update' => ["Http\\Requests\\{$model}\\Capabilities\\Translation\\Update{$model}TranslationRequest", [
                '{{ imports }}' => $this->renderUses([
                    'Heritage\\Foundation\\Http\\Attributes\\ForAction', 'Heritage\\Foundation\\Http\\FormRequest', $saveCapability,
                    ...($unique !== [] ? ['Heritage\\Database\\Eloquent\\ModelTranslation', 'Heritage\\Validation\\Rules\\UniqueTranslation', $modelClass] : []),
                ]),
                '{{ translation }}' => $unique !== [] ? "\$translation = \$this->route('".Str::snake($model)."_translation');\n\n        " : '',
                '{{ rules }}' => $this->renderTranslationRules($model, $rules, 'update', $foreignKey),
            ]],
            'request.destroy' => ["Http\\Requests\\{$model}\\Capabilities\\Translation\\Destroy{$model}TranslationRequest", []],
            'resource' => ["Http\\Resources\\{$model}\\Translation\\{$model}TranslationResource", []],
            'controller' => ["Http\\Controllers\\{$model}\\Capabilities\\{$model}TranslationController", []],
        ];

        $types = [
            'repository.interface' => 'Repository contract', 'repository' => 'Repository', 'parcel' => 'Parcel',
            'capability' => 'Capability', 'request' => 'Request', 'resource' => 'Resource', 'controller' => 'Controller',
        ];

        foreach ($files as $stub => [$class, $extra]) {
            $path = $this->getPath("{$root}\\{$class}");
            $type = $types[$stub] ?? $types[Str::before($stub, '.')];

            if ($this->files->exists($path) && ! $this->option('force')) {
                $this->components->error(sprintf('%s [%s] already exists.', $type, realpath($path)));

                continue;
            }

            $source = str_replace(array_keys([...$replace, ...$extra]), array_values([...$replace, ...$extra]), $this->files->get($this->translationStub($stub)));

            $this->makeDirectory($path);
            $this->files->put($path, $this->sortUses($source));
            $this->components->info(sprintf('%s [%s] created successfully.', $type, realpath($path)));
        }
    }

    /**
     * The rules of the translatable attributes, read from the model's store request (a plain
     * string when it has none), and whether the migrations make each unique per locale.
     *
     * @param  string  $model
     * @param  string  $table
     * @param  array<int, string>  $translatable
     * @return array<string, array{rule: string, unique: bool}>
     */
    protected function translationRules(string $model, string $table, array $translatable): array
    {
        $request = $this->getPath(trim($this->rootNamespace(), '\\')."\\Http\\Requests\\{$model}\\Capabilities\\Store{$model}Request");
        $source = $this->files->exists($request) ? Str::after($this->files->get($request), 'function rules(') : '';
        $migrations = $this->migrationsSource($table);
        $rules = [];

        foreach ($translatable as $field) {
            $rule = preg_match("/'{$field}'\\s*=>\\s*'([^']*)'/", $source, $match) ? $match[1] : 'nullable|string';
            $unique = (bool) preg_match("/\\('{$field}'[^;]*->unique\\(\\)[^;]*->translatable\\(\\)|\\('{$field}'[^;]*->translatable\\(\\)[^;]*->unique\\(\\)/", $migrations);

            $rules[$field] = ['rule' => $rule, 'unique' => $unique];
        }

        return $rules;
    }

    /**
     * The source of the migrations that create or alter a table.
     *
     * @param  string  $table
     * @return string
     */
    protected function migrationsSource(string $table): string
    {
        $artifact = $this->getArtifactOption();
        $directory = $artifact ? $this->ugarit->basePath("artifacts/{$artifact}/database/migrations") : $this->ugarit->databasePath('migrations');

        if (! $this->files->isDirectory($directory)) {
            return '';
        }

        $source = '';

        foreach ($this->files->files($directory) as $file) {
            $content = $this->files->get($file->getPathname());

            if (str_contains($content, "'{$table}'") || str_contains($content, "'".Str::singular($table)."_translations'")) {
                $source .= $content;
            }
        }

        return $source;
    }

    /**
     * The rule lines of the translated attributes; an attribute unique per locale is checked
     * with UniqueTranslation (ignoring the record of the updated translation).
     *
     * @param  string  $model
     * @param  array<string, array{rule: string, unique: bool}>  $rules
     * @param  string  $operation  "store" or "update".
     * @param  string  $foreignKey
     * @return string
     */
    protected function renderTranslationRules(string $model, array $rules, string $operation, string $foreignKey): string
    {
        $lines = [];

        foreach ($rules as $field => ['rule' => $rule, 'unique' => $unique]) {
            if (! $unique) {
                $lines[] = "            '{$field}' => '{$rule}',";

                continue;
            }

            $unique = $operation === 'store'
                ? "new UniqueTranslation({$model}::class, locale: (string) \$this->input('locale')),"
                : "new UniqueTranslation(\n                    {$model}::class,\n                    locale: \$translation instanceof ModelTranslation ? \$translation->getLocaleCode() : null,\n                    ignore: \$translation instanceof ModelTranslation ? \$translation->{$foreignKey} : null,\n                ),";
            $parts = array_map(fn (string $part) => "                '{$part}',", array_filter(explode('|', $rule), fn (string $part) => ! str_starts_with($part, 'unique')));

            $lines[] = "            '{$field}' => [\n".implode("\n", $parts)."\n                {$unique}\n            ],";
        }

        return implode("\n", $lines);
    }

    /**
     * The "use" statements of the given classes, sorted.
     *
     * @param  array<int, string>  $classes
     * @return string
     */
    protected function renderUses(array $classes): string
    {
        $classes = array_unique($classes);
        sort($classes);

        return implode("\n", array_map(fn (string $class) => "use {$class};", $classes));
    }

    /**
     * Sort the "use" statements heading a generated class.
     *
     * @param  string  $source
     * @return string
     */
    protected function sortUses(string $source): string
    {
        return preg_replace_callback('/^(?:use [^;]+;\n)+/m', function (array $block) {
            $uses = array_unique(explode("\n", trim($block[0])));
            sort($uses);

            return implode("\n", $uses)."\n";
        }, $source, 1);
    }

    /**
     * The stub of a translation slice file: the application's stubs/translation/{name}.stub,
     * else the framework's.
     *
     * @param  string  $name
     * @return string
     */
    protected function translationStub(string $name): string
    {
        return file_exists($custom = $this->ugarit->basePath("stubs/translation/{$name}.stub"))
            ? $custom
            : __DIR__."/stubs/translation/{$name}.stub";
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
