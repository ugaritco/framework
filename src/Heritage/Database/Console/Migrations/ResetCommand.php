<?php

namespace Heritage\Database\Console\Migrations;

use Heritage\Console\Command;
use Heritage\Console\ConfirmableTrait;
use Heritage\Console\Prohibitable;
use Heritage\Database\Migrations\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'migrate:reset')]
class ResetCommand extends BaseCommand
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:reset
                    {--database= : The database connection to use}
                    {--force : Force the operation to run when in production}
                    {--path=* : The path(s) to the migrations files to be executed}
                    {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                    {--pretend : Dump the SQL queries that would be run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all database migrations';

    /**
     * The migrator instance.
     *
     * @var \Heritage\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Heritage\Database\Migrations\Migrator  $migrator
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->isProhibited() || ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        return $this->migrator->usingConnection($this->option('database'), function () {
            // First, we'll make sure that the migration table actually exists before we
            // start trying to rollback and re-run all of the migrations. If it's not
            // present we'll just bail out with an info message for the developers.
            if (! $this->migrator->repositoryExists()) {
                return $this->components->warn('Migration table not found.');
            }

            $this->migrator->setOutput($this->output)->reset(
                $this->getMigrationPaths(), $this->option('pretend')
            );
        });
    }
}
