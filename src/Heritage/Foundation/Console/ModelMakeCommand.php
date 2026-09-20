<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\Concerns\CreatesMatchingTest;
use Heritage\Console\GeneratorCommand;
use Heritage\Support\Collection;
use Heritage\Support\Str;
use Heritage\Support\Stringable;
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
    use CreatesMatchingTest;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:model
                    {name : The name of the model}
                    {--a|all : Generate a migration, seeder, factory, policy, resource controller, and form request classes for the model}
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
                    {--t|translation : Create a new translation model and configure the model with translations}
                    {--trans : Create a new translation model and configure the model with translations}
                    {--translatable : Create a new translation model and configure the model with translations}
                    {--translation-model : Indicates if the generated model should be a translation model extending ModelTranslation}
                    {--artifact= : The target modular artifact for this model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class inside a modular artifact';

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
        // 1. Resolve target modular artifact
        $artifact = $this->option('artifact');

        if (! $artifact && $this->input->isInteractive()) {
            $available = $this->getAvailableArtifacts();

            if (! empty($available)) {
                $artifact = select(
                    label: 'Which artifact does this model belong to?',
                    options: $available,
                );
            } else {
                $artifact = text(
                    label: 'What is the target artifact name for this model?',
                    placeholder: 'e.g. catalog, identity',
                    required: true,
                );
            }
        }

        if ($artifact) {
            $this->input->setOption('artifact', $artifact);
        }

        // 2. Resolve translation support
        $hasTranslatableOption = $this->option('translation') || $this->option('trans') || $this->option('translatable');

        if (! $hasTranslatableOption && ! $this->option('translation-model') && $this->input->isInteractive()) {
            $isTranslatable = confirm(
                label: 'Should this model support multilingual translations (HasTranslation)?',
                default: false,
            );

            if ($isTranslatable) {
                $this->input->setOption('translatable', true);
            }
        }

        if ($this->option('translation-model')) {
            $this->type = 'Translation model';
        }

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

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
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

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        } elseif ($this->option('requests')) {
            $this->createFormRequests();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }

        if ($this->isTranslatable() && ! $this->option('translation-model')) {
            $this->createTranslationModel();
        }

        return true;
    }

    /**
     * Determine if the model should be configured with translations.
     *
     * @return bool
     */
    protected function isTranslatable(): bool
    {
        return (bool) ($this->option('translation') || $this->option('translatable') || $this->option('trans'));
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
        $factory = Str::studly($this->argument('name'));
        $artifact = $this->option('artifact');

        $this->call('make:factory', array_filter([
            'name' => "{$factory}Factory",
            '--model' => $this->qualifyClass($this->getNameInput()),
            '--artifact' => $artifact,
        ]));
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
            '--translation' => $this->isTranslatable() ? true : null,
            '--artifact' => $artifact,
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
        $artifact = $this->option('artifact') ?: $this->option('module');

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
     * Create the form requests for the model.
     *
     * @return void
     */
    protected function createFormRequests()
    {
        $request = Str::studly(class_basename($this->argument('name')));
        $artifact = $this->option('artifact') ?: $this->option('module');

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
        $artifact = $this->option('artifact') ?: $this->option('module');

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

        return $this->resolveStubPath('/stubs/model.stub');
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

        if ($artifact) {
            $studly = Str::studly($artifact);
            return "Ugarit\\Artifacts\\{$studly}\\Models";
        }

        return is_dir(app_path('Models')) ? $rootNamespace.'\\Models' : $rootNamespace;
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

        if ($artifact) {
            $modelBase = class_basename($name);
            $dir = $this->ugarit->basePath("artifacts/{$artifact}/src/Models");

            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            return $dir.'/'.$modelBase.'.php';
        }

        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->ugarit['path'].'/'.str_replace('\\', '/', $name).'.php';
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
        $replace = array_merge(
            $this->buildFactoryReplacements(),
            $this->buildTranslationReplacements()
        );

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Build the replacements for a factory.
     *
     * @return array<string, string>
     */
    protected function buildFactoryReplacements()
    {
        $replacements = [];

        if ($this->option('factory') || $this->option('all')) {
            $modelPath = (new Stringable($this->argument('name')))->studly()->replace('/', '\\')->toString();
            $artifact = $this->option('artifact');

            if ($artifact) {
                $studly = Str::studly($artifact);
                $factoryNamespace = "\\Ugarit\\Artifacts\\{$studly}\\Database\\Factories\\{$modelPath}Factory";
            } else {
                $factoryNamespace = '\\Database\\Factories\\'.$modelPath.'Factory';
            }

            $factoryCode = <<<EOT
            /** @use HasFactory<$factoryNamespace> */
                use HasFactory;
            EOT;

            $replacements['{{ factory }}'] = $factoryCode;
            $replacements['{{ factoryImport }}'] = "use Heritage\Database\Eloquent\Factories\HasFactory;\n";
        } else {
            $replacements['{{ factory }}'] = ($this->isTranslatable() && ! $this->option('translation-model')) ? '' : "    //\n";
            $replacements["{{ factoryImport }}\n"] = '';
            $replacements["{{ factoryImport }}\r\n"] = '';
            $replacements['{{ factoryImport }}'] = '';
        }

        return $replacements;
    }

    /**
     * Build the replacements for translation capabilities.
     *
     * @return array<string, string>
     */
    protected function buildTranslationReplacements(): array
    {
        $replacements = [];

        if ($this->isTranslatable() && ! $this->option('translation-model')) {
            $gap = ($this->option('factory') || $this->option('all')) ? "\n" : '';
            $replacements['{{ translationImport }}'] = "use Heritage\Database\Eloquent\Concerns\HasTranslation;\n";
            $replacements['{{ translation }}'] = "{$gap}    use HasTranslation;\n\n    /**\n     * The attributes that are translatable into multiple locales.\n     *\n     * @var array<int, string>\n     */\n    protected array \$translatable = [\n        //\n    ];\n";
        } else {
            $replacements['{{ translation }}'] = '';
            $replacements["{{ translationImport }}\n"] = '';
            $replacements["{{ translationImport }}\r\n"] = '';
            $replacements['{{ translationImport }}'] = '';
        }

        return $replacements;
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

        (new Collection(multiselect('Would you like any of the following?', [
            'seed' => 'Database Seeder',
            'factory' => 'Factory',
            'requests' => 'Form Requests',
            'migration' => 'Migration',
            'policy' => 'Policy',
            'resource' => 'Resource Controller',
            'translation' => 'Translation Model',
        ])))->each(fn ($option) => $input->setOption($option, true));
    }
}
