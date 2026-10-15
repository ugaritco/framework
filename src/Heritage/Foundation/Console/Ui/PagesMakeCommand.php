<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Ui;

use Heritage\Console\Command;
use Heritage\Filesystem\Filesystem;
use Heritage\Foundation\Console\Ui\Concerns\GeneratesUi;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:pages')]
class PagesMakeCommand extends Command
{
    use GeneratesUi;

    /**
     * The operations a model's pages offer, each with the routes it needs; a translatable model
     * also needs its {model}-feature store and update routes, which its feature forms post to.
     *
     * @var array<string, array<int, string>>
     */
    protected const OPERATIONS = [
        'index' => ['{model}.index'],
        'show' => ['{model}.show'],
        'create' => ['{model}.create', '{model}.store'],
        'edit' => ['{model}.edit', '{model}.update'],
        'destroy' => ['{model}.destroy'],
        'translations' => ['{model}-translation.store', '{model}-translation.update', '{model}-translation.destroy'],
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:pages
                    {model : The model the pages present (e.g. Book)}
                    {--artifact= : The artifact holding the model}
                    {--icon= : The Lucide icon of the model in the artifact navigation (e.g. BookOpen)}
                    {--only= : The operations to generate, separated by commas: index, show, create, edit, destroy, translations}
                    {--except= : The operations to leave out, separated by commas}
                    {--stack= : The frontend stack (vue); detected from package.json by default}
                    {--f|force : Overwrite the files that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the panel pages and components of a model (CRUD and translations), and its item in the artifact navigation';

    /**
     * Create a new command instance.
     *
     * @param  Filesystem  $files
     */
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $artifact = (string) $this->option('artifact');
        $model = Str::studly((string) $this->argument('model'));

        if ($artifact === '') {
            $this->components->error('Give the artifact of the model with --artifact.');

            return self::FAILURE;
        }

        $class = Blueprint::modelClass($artifact, $model);

        if (! class_exists($class)) {
            $this->components->error("The model [{$class}] does not exist; create it with make:model {$model} --artifact={$artifact} --all.");

            return self::FAILURE;
        }

        if (! $stack = $this->stack()) {
            return self::FAILURE;
        }

        $blueprint = Blueprint::read($this->files, $this->ugarit->basePath(), $artifact, $model);
        $operations = $this->operations($blueprint);

        if ($operations === null) {
            return self::FAILURE;
        }

        if (! $this->files->exists($this->artifactPath($artifact, 'navigation.ts'))) {
            $this->call('make:panel', ['artifact' => $artifact, '--stack' => $stack->name()]);
        }

        $this->writeFiles($this->artifactPath($artifact), $stack->pageFiles($blueprint, $operations), $stack->pageReplacements($blueprint, $operations));

        if (in_array('index', $operations, true)) {
            $this->applyEdits($stack, [$stack->navigationEdit($blueprint, $this->iconName($this->option('icon'), 'Box'))]);
        }

        $this->printReport();
        $this->refreshFrontend();

        return self::SUCCESS;
    }

    /**
     * The operations to generate: those given by --only (all by default), but those of --except.
     * The translations of a model without translatable fields, the translations without the show
     * page holding their tab, and an operation whose routes are not registered are left out; an
     * unknown operation, or one asked by --only that cannot be generated, is an error (null).
     *
     * @param  Blueprint  $blueprint
     * @return array<int, string>|null
     */
    protected function operations(Blueprint $blueprint): ?array
    {
        $only = $this->list('only');
        $except = $this->list('except');
        $unknown = array_diff([...$only, ...$except], array_keys(self::OPERATIONS));

        if ($unknown !== []) {
            $this->components->error('Unknown operations ['.implode(', ', $unknown).']; choose among: '.implode(', ', array_keys(self::OPERATIONS)).'.');

            return null;
        }

        $operations = array_values(array_diff($only === [] ? array_keys(self::OPERATIONS) : $only, $except));
        $reasons = [];

        if (! $blueprint->isTranslatable()) {
            $reasons['translations'] = "the model [{$blueprint->model}] has no translatable field";
        } elseif (! in_array('show', $operations, true)) {
            $reasons['translations'] = 'its tab is on the show page, which is not generated';
        }

        $features = $blueprint->isTranslatable() ? ['create' => ['{model}-feature.store'], 'edit' => ['{model}-feature.update']] : [];

        foreach ($operations as $operation) {
            $missing = array_filter(
                array_map(fn (string $route) => str_replace('{model}', Str::kebab($blueprint->model), $route), [...self::OPERATIONS[$operation], ...($features[$operation] ?? [])]),
                fn (string $route) => ! $this->ugarit['router']->has($route)
            );

            if ($missing !== [] && ! isset($reasons[$operation])) {
                $reasons[$operation] = 'its routes are not registered ('.implode(', ', $missing).')';
            }
        }

        foreach ($reasons as $operation => $reason) {
            if (! in_array($operation, $operations, true)) {
                continue;
            }

            if (in_array($operation, $only, true)) {
                $this->components->error("The [{$operation}] operation cannot be generated: {$reason}.");

                return null;
            }

            if ($operation !== 'translations' || $blueprint->isTranslatable()) {
                $this->components->warn("The [{$operation}] operation is left out: {$reason}.");
            }

            $operations = array_values(array_diff($operations, [$operation]));
        }

        if ($operations === []) {
            $this->components->error('No operation is left to generate.');

            return null;
        }

        return $operations;
    }

    /**
     * The comma separated values of an option.
     *
     * @param  string  $option
     * @return array<int, string>
     */
    protected function list(string $option): array
    {
        return array_values(array_filter(array_map(fn (string $value) => Str::lower(trim($value)), explode(',', (string) $this->option($option)))));
    }
}
