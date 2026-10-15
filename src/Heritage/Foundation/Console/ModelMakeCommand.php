<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\Concerns\CreatesMatchingTest;
use Heritage\Console\GeneratorCommand;
use Heritage\Database\Console\Field;
use Heritage\Database\Eloquent\Model;
use Heritage\Foundation\Console\Concerns\EditsSource;
use Heritage\Support\Collection;
use Heritage\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Ugarit\Prompts\confirm;
use function Ugarit\Prompts\multiselect;
use function Ugarit\Prompts\select;
use function Ugarit\Prompts\text;

#[AsCommand(name: 'make:model')]
class ModelMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest, EditsSource;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:model
                    {name : The name of the model}
                    {--a|all : Generate a migration, seeder, factory and the capabilities slice (make:capabilities) with its routes for the model}
                    {--c|controller : Create a new controller for the model}
                    {--f|factory : Create a new factory for the model}
                    {--force : Create the class even if the model already exists}
                    {--m|migration : Create a new migration file for the model}
                    {--morph-pivot : Indicates if the generated model should be a custom polymorphic intermediate table model}
                    {--policy : Create a new policy for the model}
                    {--s|seed : Create a new seeder for the model}
                    {--p|pivot : Indicates if the generated model should be a custom intermediate table model}
                    {--r|resource : Indicates if the generated controller should be a resource controller}
                    {--api : Indicates if the generated controller should be an API resource controller}
                    {--R|requests : Create new form request classes and use them in the resource controller}
                    {--t|translatable : Make the model translatable ($translatable attributes stored in a translation table)}
                    {--with-translation-file : Generate a physical Translation model file in addition to the main model}
                    {--translation-model : Indicates if the generated model should be an explicit translation model extending ModelTranslation}
                    {--artifact= : The target modular artifact for this model}
                    {--fields= : The fields, as name:type[:modifier...] separated by commas (e.g. branch_id:foreignId,slug:string:unique,name:string:translatable,active:boolean)}';

    /**
     * The fields of the model being generated.
     *
     * @var array<int, Field>
     */
    protected array $fields = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class inside an application or modular artifact';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        // 1. Resolve target modular artifact or application location
        $artifact = $this->option('artifact');

        if (! $artifact && ! $this->option('translation-model') && $this->input->isInteractive()) {
            $available = $this->getAvailableArtifacts();

            $locations = [
                'app' => 'Application Root (App\Models)',
            ];

            foreach ($available as $artSlug) {
                $studly = Str::studly($artSlug);
                $locations[$artSlug] = "Domain Artifact: {$studly} (artifacts/{$artSlug}/src/Models)";
            }

            $locations['custom_artifact'] = 'New Artifact (Specify a new Domain Artifact)...';

            $chosenLocation = select(
                label: 'Where should this model be created?',
                options: $locations,
                default: ! empty($available) ? $available[0] : 'app',
            );

            if ($chosenLocation === 'custom_artifact') {
                $artifact = text(
                    label: 'What is the target artifact name for this model?',
                    placeholder: 'e.g. catalog, identity, billing',
                    required: true,
                    validate: fn (string $v) => preg_match('/^[a-z][a-z0-9\-]*$/', $v)
                        ? null
                        : 'Name must be lowercase letters, numbers, and dashes only.',
                );
            } elseif ($chosenLocation !== 'app') {
                $artifact = $chosenLocation;
            } else {
                $artifact = null;
            }
        }

        if ($artifact) {
            $this->input->setOption('artifact', $artifact);
        }

        // 2. Resolve the fields; a translatable field makes the model translatable
        $fields = [];

        if ($this->generatesFields()) {
            $fields = $this->option('fields') ? Field::parseMany($this->option('fields')) : ($this->input->isInteractive() ? $this->promptForFields() : []);
        }

        if (array_filter($fields, fn (Field $field) => $field->has('translatable'))) {
            $this->input->setOption('translatable', true);
        }

        // 3. Resolve translation support when the fields did not decide it
        $hasTranslatableOption = $this->option('translatable');

        if (! $hasTranslatableOption && $fields === [] && ! $this->option('translation-model') && $this->input->isInteractive()) {
            $isTranslatable = confirm(
                label: 'Would you like this model to be translatable?',
                default: false,
            );

            if ($isTranslatable) {
                $this->input->setOption('translatable', true);
            }
        }

        if ($this->option('translation-model')) {
            $this->type = 'Translation model';
        }

        $this->fields = $this->generatesFields() ? ($fields ?: $this->defaultFields()) : [];

        if (parent::handle() === false && ! $this->option('force')) {
            if (! $this->alreadyExists($this->getNameInput())) {
                return false;
            }

            if (! confirm('Do you want to generate additional components for the model?')) {
                return false;
            } else {
                $this->afterPromptingForMissingArguments($this->input, $this->output);
            }
        }

        $this->addInverseRelations();

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('all')) {
            $this->createCapabilities();
        } else {
            if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
                $this->createController();
            } elseif ($this->option('requests')) {
                $this->createFormRequests();
            }

            if ($this->option('policy')) {
                $this->createPolicy();
            }
        }

        if ($this->isTranslatable() && $this->option('with-translation-file') && ! $this->option('translation-model')) {
            $this->createTranslationModel();
        }

        return null;
    }

    /**
     * Whether the model's attributes come from fields (every model but pivots and translation models).
     *
     * @return bool
     */
    protected function generatesFields(): bool
    {
        return ! $this->option('pivot') && ! $this->option('morph-pivot') && ! $this->option('translation-model');
    }

    /**
     * Ask for the fields one by one, until an empty name.
     *
     * @return array<int, Field>
     */
    protected function promptForFields(): array
    {
        $this->components->info('Define the fields of the model; leave the name empty to finish (no field uses name, slug and active).');

        $fields = [];

        while (($name = trim(text(
            label: 'Field name',
            placeholder: 'e.g. title, price, branch_id',
            validate: fn (string $value) => $value === '' || preg_match('/^[a-z][a-z0-9_]*$/', $value) ? null : 'Use snake_case.',
            hint: 'Leave empty to finish.',
        ))) !== '') {
            $type = select(
                label: "Type of [{$name}]",
                options: array_keys(Field::TYPES),
                default: str_ends_with($name, '_id') ? 'foreignId' : 'string',
            );

            $modifiers = multiselect(
                label: "Modifiers of [{$name}]",
                options: $type === 'foreignId' ? array_values(array_diff(Field::MODIFIERS, ['translatable'])) : Field::MODIFIERS,
            );

            $fields[] = Field::parse(implode(':', [$name, $type, ...$modifiers]));
        }

        return $fields;
    }

    /**
     * The fields of a model given none: a name (translatable on a translatable model), a unique
     * slug and an active flag.
     *
     * @return array<int, Field>
     */
    protected function defaultFields(): array
    {
        return Field::parseMany($this->isTranslatable()
            ? 'slug:string:unique,name:string:translatable,active:boolean'
            : 'name:string,slug:string:unique,active:boolean');
    }

    /**
     * Determine if the model should be configured with translations.
     *
     * @return bool
     */
    protected function isTranslatable(): bool
    {
        return (bool) $this->option('translatable');
    }

    /**
     * Get the name of the translation model for the current model.
     *
     * @return string
     */
    protected function getTranslationModelName(): string
    {
        $name = trim($this->argument('name'));

        return $name.'Translation';
    }

    /**
     * Create a translation model for the model.
     *
     * @return void
     */
    protected function createTranslationModel(): void
    {
        $translationModel = $this->getTranslationModelName();
        $artifact = $this->option('artifact');

        $this->call('make:model', array_filter([
            'name' => $translationModel,
            '--translation-model' => true,
            '--force' => $this->option('force'),
            '--artifact' => $artifact,
        ]));
    }

    /**
     * Create a model factory for the model.
     *
     * @return void
     */
    protected function createFactory()
    {
        $modelName = Str::studly(class_basename($this->argument('name')));
        $artifact = $this->option('artifact');

        $this->call('make:factory', array_filter([
            'name' => "{$modelName}Factory",
            '--model' => $this->qualifyClass($this->getNameInput()),
            '--artifact' => $artifact,
        ]));

        if ($this->isTranslatable() && $this->option('with-translation-file') && ! $this->option('translation-model')) {
            $this->call('make:factory', array_filter([
                'name' => "{$modelName}TranslationFactory",
                '--model' => $this->qualifyClass($modelName.'Translation'),
                '--artifact' => $artifact,
            ]));
        }
    }

    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration()
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $artifact = $this->option('artifact');

        $this->call('make:migration', array_filter([
            'name' => "create_{$table}_table",
            '--create' => $table,
            '--translatable' => $this->isTranslatable() ? true : null,
            '--artifact' => $artifact,
            '--fields' => $this->fields !== [] ? implode(',', array_map(fn (Field $field) => $field->definition(), $this->fields)) : null,
        ]));
    }

    /**
     * Create a seeder file for the model.
     *
     * @return void
     */
    protected function createSeeder()
    {
        $seeder = Str::studly(class_basename($this->argument('name')));
        $artifact = $this->option('artifact');

        $this->call('make:seeder', array_filter([
            'name' => "{$seeder}Seeder",
            '--model' => $this->argument('name'),
            '--artifact' => $artifact,
        ]));
    }

    /**
     * Create a controller for the model.
     *
     * @return void
     */
    protected function createController()
    {
        $controller = Str::studly(class_basename($this->argument('name')));
        $modelName = $this->qualifyClass($this->getNameInput());
        $artifact = $this->option('artifact');

        $this->call('make:controller', array_filter([
            'name' => "{$controller}Controller",
            '--model' => $this->option('resource') || $this->option('api') ? $modelName : null,
            '--api' => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
            '--test' => $this->option('test'),
            '--pest' => $this->option('pest'),
            '--artifact' => $artifact,
        ]));
    }

    /**
     * Create the capabilities slice of the model (repository through controller) and register
     * its routes.
     *
     * @return void
     */
    protected function createCapabilities()
    {
        $this->call('make:capabilities', array_filter([
            'name' => Str::studly(class_basename($this->argument('name'))),
            '--artifact' => $this->option('artifact'),
            '--force' => $this->option('force'),
            '--routes' => true,
            '--nullable' => implode(',', array_map(
                fn (Field $field) => $field->name,
                array_filter($this->fields, fn (Field $field) => $field->has('nullable'))
            )),
        ]));
    }

    /**
     * Create the form requests for the model.
     *
     * @return void
     */
    protected function createFormRequests()
    {
        $request = Str::studly(class_basename($this->argument('name')));
        $artifact = $this->option('artifact');

        $this->call('make:request', array_filter([
            'name' => "Store{$request}Request",
            '--artifact' => $artifact,
        ]));

        $this->call('make:request', array_filter([
            'name' => "Update{$request}Request",
            '--artifact' => $artifact,
        ]));
    }

    /**
     * Create a policy file for the model.
     *
     * @return void
     */
    protected function createPolicy()
    {
        $policy = Str::studly(class_basename($this->argument('name')));
        $artifact = $this->option('artifact');

        $this->call('make:policy', array_filter([
            'name' => "{$policy}Policy",
            '--model' => $this->qualifyClass($this->getNameInput()),
            '--artifact' => $artifact,
        ]));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('pivot')) {
            return $this->resolveStubPath('/stubs/model.pivot.stub');
        }

        if ($this->option('morph-pivot')) {
            return $this->resolveStubPath('/stubs/model.morph-pivot.stub');
        }

        if ($this->option('translation-model')) {
            return $this->resolveStubPath('/stubs/model.translation.stub');
        }

        if ($this->isTranslatable()) {
            return $this->resolveStubPath('/stubs/model.stub');
        }

        return $this->resolveStubPath('/stubs/model.plain.stub');
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
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        $artifact = $this->option('artifact');

        if ($artifact) {
            $studly = Str::studly($artifact);
            return "Ugarit\\Artifacts\\{$studly}\\";
        }

        return parent::rootNamespace();
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        $artifact = $this->option('artifact');

        return $artifact
            ? "Ugarit\\Artifacts\\" . Str::studly($artifact) . "\\Models"
            : $rootNamespace . '\\Models';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $artifact = $this->option('artifact');
        $modelBase = class_basename($name);

        $baseDir = $artifact
            ? $this->ugarit->basePath("artifacts/{$artifact}/src/Models")
            : $this->ugarit['path'] . '/Models';

        if (! is_dir($baseDir)) {
            @mkdir($baseDir, 0755, true);
        }

        return "{$baseDir}/{$modelBase}.php";
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Heritage\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $modelBase = class_basename($name);
        $replacements = [];

        if ($this->option('translation-model')) {
            $parentModel = Str::beforeLast($modelBase, 'Translation');
            $snakeModel = Str::snake($parentModel);
            $camelModel = lcfirst($parentModel);

            $replacements = [
                '{{ model }}' => $parentModel,
                '{{snake_model}}' => $snakeModel,
                '{{ snake_model }}' => $snakeModel,
                '{{camel_model}}' => $camelModel,
                '{{ camel_model }}' => $camelModel,
                '{{foreign_key}}' => "{$snakeModel}_id",
                '{{ foreign_key }}' => "{$snakeModel}_id",
            ];
        }

        $classContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            parent::buildClass($name)
        );

        return $this->generatesFields() ? $this->fillFields($classContent, $modelBase) : $classContent;
    }

    /**
     * Fill the model's fillable, translatable, casts, translation fallbacks and the relations of
     * its foreign keys from the fields; a related model found in another artifact is imported.
     *
     * @param  string  $class
     * @param  string  $model
     * @return string
     */
    protected function fillFields(string $class, string $model): string
    {
        $columns = array_filter($this->fields, fn (Field $field) => ! $field->has('translatable'));
        $translatable = array_filter($this->fields, fn (Field $field) => $field->has('translatable'));
        $hasSlug = (bool) array_filter($columns, fn (Field $field) => $field->name === 'slug');
        $relations = array_filter($columns, fn (Field $field) => $field->relation() !== null);

        $quote = fn (Field $field) => "        '{$field->name}',";

        $lines = [
            'fillable' => array_map($quote, $columns),
            'translatable' => array_map($quote, $translatable),
            'casts' => array_map(fn (Field $field) => "        '{$field->name}' => '{$field->cast()}',", array_filter($columns, fn (Field $field) => $field->cast() !== null)),
            'fallback' => $hasSlug ? array_map(fn (Field $field) => "        '{$field->name}' => 'slug',", array_filter($translatable, fn (Field $field) => $field->type === 'string')) : [],
        ];

        foreach ($lines as $placeholder => $values) {
            $class = preg_replace_callback(
                '/^\{\{ '.$placeholder.' \}\}\r?\n/m',
                fn () => implode('', array_map(fn ($line) => $line."\n", $values)),
                $class
            );
        }

        $imports = [
            'Heritage\\Database\\Eloquent\\Factories\\HasFactory',
            'Heritage\\Database\\Eloquent\\Model',
            'Heritage\\Database\\Eloquent\\SoftDeletes',
            ...($relations !== [] ? ['Heritage\\Database\\Eloquent\\Relations\\BelongsTo'] : []),
            ...$this->externalRelatedModels($relations),
        ];
        sort($imports);

        return str_replace(
            ['{{ imports }}', '{{ relations }}'],
            [implode("\n", array_map(fn ($import) => "use {$import};", $imports)), implode('', array_map(fn (Field $field) => $field->belongsTo($model), $relations))],
            $class
        );
    }

    /**
     * The related models of foreign keys that are not in the model's folder but in another
     * artifact's Models folder (e.g. country_id of i18n's Locale: Geography's Country).
     *
     * @param  array<int, Field>  $relations  The foreign key fields.
     * @return array<int, string> The classes to import.
     */
    protected function externalRelatedModels(array $relations): array
    {
        $folder = Str::beforeLast($this->qualifyClass($this->getNameInput()), '\\');
        $imports = [];

        foreach ($relations as $field) {
            $related = Str::studly($field->relation());

            if (is_subclass_of("{$folder}\\{$related}", Model::class)) {
                continue;
            }

            foreach ($this->getAvailableArtifacts() as $artifact) {
                $class = 'Ugarit\\Artifacts\\'.Str::studly($artifact).'\\Models\\'.$related;

                if (is_subclass_of($class, Model::class)) {
                    $imports[] = $class;

                    break;
                }
            }
        }

        return array_values(array_unique($imports));
    }

    /**
     * Add the hasMany() inverse of each foreign key to its related model, when it exists in the
     * model's folder.
     *
     * @return void
     */
    protected function addInverseRelations(): void
    {
        $model = $this->qualifyClass($this->getNameInput());

        foreach ($this->fields as $field) {
            if ($field->relation() === null) {
                continue;
            }

            $related = Str::beforeLast($model, '\\').'\\'.Str::studly($field->relation());

            if ($related === $model || ! class_exists($related)) {
                continue;
            }

            $path = (new ReflectionClass($related))->getFileName();
            $source = $this->files->get($path);
            $edited = $this->addHasMany($source, $field, class_basename($model));

            if ($edited !== $source) {
                $this->files->put($path, $edited);
                $this->components->info(sprintf('Model [%s] related with [%s()].', $path, $field->inverse(class_basename($model))));
            }
        }
    }

    /**
     * Get the list of available modular artifacts.
     *
     * @return array<int, string>
     */
    protected function getAvailableArtifacts(): array
    {
        $artifactsPath = $this->ugarit->basePath('artifacts');

        if (! is_dir($artifactsPath)) {
            return [];
        }

        $dirs = array_filter(glob($artifactsPath.'/*'), 'is_dir');

        return array_values(array_map('basename', $dirs));
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $choices = multiselect(
            label: 'Would you like any of the following?',
            options: [
                'all' => 'With All (Factory, Migration, Seeder, Capabilities Slice and its Routes)',
                'seed' => 'Database Seeder',
                'factory' => 'Factory',
                'requests' => 'Form Requests',
                'migration' => 'Migration',
                'policy' => 'Policy',
                'resource' => 'Resource Controller',
                'translatable' => 'Translatable (Multilingual Model & Translation Class)',
            ],
        );

        if (in_array('all', $choices)) {
            $input->setOption('all', true);
            $input->setOption('factory', true);
            $input->setOption('seed', true);
            $input->setOption('migration', true);
        } else {
            (new Collection($choices))->each(fn ($option) => $input->setOption($option, true));
        }
    }
}
