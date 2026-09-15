<?php

namespace Heritage\Database\Console;

use Heritage\Console\Command;
use Heritage\Console\Prohibitable;
use Heritage\Contracts\Events\Dispatcher;
use Heritage\Database\Connection;
use Heritage\Database\ConnectionResolverInterface;
use Heritage\Database\Events\MigrationsPruned;
use Heritage\Database\Events\SchemaDumped;
use Heritage\Filesystem\Filesystem;
use Heritage\Support\Facades\Config;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schema:dump')]
class DumpCommand extends Command
{
    use Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schema:dump
                {--database= : The database connection to use}
                {--path= : The path where the schema dump file should be stored}
                {--prune : Delete all existing migration files}
                {--without-migration-data : Dump the schema without the migration data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump the given database schema';

    /**
     * Execute the console command.
     *
     * @param  \Heritage\Database\ConnectionResolverInterface  $connections
     * @param  \Heritage\Contracts\Events\Dispatcher  $dispatcher
     * @return int
     */
    public function handle(ConnectionResolverInterface $connections, Dispatcher $dispatcher)
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $connection = $connections->connection($database = $this->input->getOption('database'));

        $this->schemaState($connection)->dump(
            $connection, $path = $this->path($connection)
        );

        $dispatcher->dispatch(new SchemaDumped($connection, $path));

        $info = 'Database schema dumped';

        if ($this->option('prune')) {
            (new Filesystem)->deleteDirectory(
                $path = database_path('migrations'), preserve: false
            );

            $info .= ' and pruned';

            $dispatcher->dispatch(new MigrationsPruned($connection, $path));
        }

        $this->components->info($info.' successfully.');

        return Command::SUCCESS;
    }

    /**
     * Create a schema state instance for the given connection.
     *
     * @param  \Heritage\Database\Connection  $connection
     * @return mixed
     */
    protected function schemaState(Connection $connection)
    {
        $migrations = Config::get('database.migrations', 'migrations');

        $migrationTable = is_array($migrations) ? ($migrations['table'] ?? 'migrations') : $migrations;

        if ($this->option('without-migration-data')) {
            $migrationTable = null;
        }

        return $connection->getSchemaState()
            ->withMigrationTable($migrationTable)
            ->handleOutputUsing(function ($type, $buffer) {
                $this->output->write($buffer);
            });
    }

    /**
     * Get the path that the dump should be written to.
     *
     * @param  \Heritage\Database\Connection  $connection
     */
    protected function path(Connection $connection)
    {
        return tap($this->option('path') ?: database_path('schema/'.$connection->getName().'-schema.sql'), function ($path) {
            (new Filesystem)->ensureDirectoryExists(dirname($path));
        });
    }
}
