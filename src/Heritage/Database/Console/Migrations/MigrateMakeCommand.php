<?php

declare(strict_types=1);

namespace Heritage\Database\Console\Migrations;

use Heritage\Contracts\Console\PromptsForMissingInput;
use Heritage\Database\Migrations\MigrationCreator;
use Heritage\Support\Composer;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Ugarit\Prompts\confirm;
use function Ugarit\Prompts\select;
use function Ugarit\Prompts\text;

#[AsCommand(name: 'make:migration')]
class MigrateMakeCommand extends BaseCommand implements PromptsForMissingInput
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:migration {name : The name of the migration}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration (Deprecated)}
        {--t|trans : Create a new migration with a translation table}
        {--translation : Create a new migration with a translation table}
        {--translatable : Create a new migration with a translation table}
        {--artifact= : The target modular artifact for this migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file';

    /**
     * The migration creator instance.
     *
     * @var \Heritage\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Heritage\Support\Composer
     *
     * @deprecated Will be removed in a future Ugarit version.
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Heritage\Database\Migrations\MigrationCreator  $creator
     * @param  \Heritage\Support\Composer  $composer
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $name = Str::snake(trim($this->input->getArgument('name')));

        $table = $this->input->getOption('table');

        $create = $this->input->getOption('create') ?: false;

        // Resolve target modular artifact
        $artifact = $this->input->getOption('artifact');

        if (! $artifact && $this->input->isInteractive()) {
            $available = $this->getAvailableArtifacts();

            if (! empty($available)) {
                $artifact = select(
                    label: 'Which artifact does this migration belong to?',
                    options: $available,
                );
            } else {
                $artifact = text(
                    label: 'What is the target artifact name for this migration?',
                    placeholder: 'e.g. catalog, identity',
                    required: true,
                );
            }
        }

        if ($artifact) {
            $this->input->setOption('artifact', $artifact);
        }

        // Resolve translatable status
        $hasTranslatableOption = $this->input->getOption('trans')
            || $this->input->getOption('translation')
            || $this->input->getOption('translatable');

        if (! $hasTranslatableOption && $this->input->isInteractive()) {
            $translatable = confirm(
                label: 'Should this migration support multilingual translations (Schema::createWithTranslation)?',
                default: false,
            );
        } else {
            $translatable = (bool) $hasTranslatableOption;
        }

        // If no table was given as an option but a create option is given then we
        // will use the "create" option as the table name.
        if (! $table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        // If translatable option is set, we treat it as table creation if not updating
        if ($translatable && ! $table) {
            [$table, $guessedCreate] = TableGuesser::guess($name);
            $create = true;
        } elseif (! $table) {
            [$table, $create] = TableGuesser::guess($name);
        }

        // Fallback: If table name could not be guessed, resolve it cleanly from the migration name
        if (! $table) {
            $cleanName = (string) preg_replace('/^create_/', '', $name);
            $cleanName = (string) preg_replace('/_table$/', '', $cleanName);

            $table = Str::snake(Str::pluralStudly($cleanName));
            $create = true;
        }

        if ($translatable) {
            $create = true;
        }

        // Write the migration out to disk
        $this->writeMigration($name, $table, $create, $translatable);
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool  $create
     * @param  bool  $translatable
     * @return void
     */
    protected function writeMigration($name, $table, $create, $translatable = false)
    {
        $file = $this->creator->create(
            $name, $this->getMigrationPath(), $table, $create, $translatable
        );

        if (windows_os()) {
            $file = str_replace('/', '\\', $file);
        }

        $this->components->info(sprintf('Migration [%s] created successfully.', $file));
    }

    /**
     * Get migration path (either specified by '--path' option, artifact, or default location).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        if (! is_null($targetPath = $this->input->getOption('path'))) {
            return ! $this->usingRealPath()
                ? $this->ugarit->basePath().'/'.$targetPath
                : $targetPath;
        }

        $artifact = $this->input->getOption('artifact');

        if ($artifact) {
            $dir = $this->ugarit->basePath('artifacts/'.$artifact.'/database/migrations');

            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            return $dir;
        }

        return parent::getMigrationPath();
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
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, array<int, string>>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => ['What should the migration be named?', 'E.g. create_flights_table'],
        ];
    }
}
