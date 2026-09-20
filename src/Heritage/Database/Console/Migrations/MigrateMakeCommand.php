<?php

namespace Heritage\Database\Console\Migrations;

use Heritage\Contracts\Console\PromptsForMissingInput;
use Heritage\Database\Migrations\MigrationCreator;
use Heritage\Support\Composer;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

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
        {--translatable : Create a new migration with a translation table}';

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
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $name = Str::snake(trim($this->input->getArgument('name')));

        $table = $this->input->getOption('table');

        $create = $this->input->getOption('create') ?: false;

        $translatable = $this->input->getOption('trans')
            || $this->input->getOption('translation')
            || $this->input->getOption('translatable');

        // If no table was given as an option but a create option is given then we
        // will use the "create" option as the table name. This allows the devs
        // to pass a table name into this option as a short-cut for creating.
        if (! $table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        // If translatable option is set, we treat it as table creation if not updating
        if ($translatable && ! $table) {
            [$table, $guessedCreate] = TableGuesser::guess($name);
            $create = true;
        } elseif (! $table) {
            // Next, we will attempt to guess the table name if this the migration has
            // "create" in the name. This will allow us to provide a convenient way
            // of creating migrations that create new tables for the application.
            [$table, $create] = TableGuesser::guess($name);
        }

        if ($translatable) {
            $create = true;
        }

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
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
     * Get migration path (either specified by '--path' option or default location).
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

        return parent::getMigrationPath();
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => ['What should the migration be named?', 'E.g. create_flights_table'],
        ];
    }
}
