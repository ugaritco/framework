<?php

namespace Heritage\Database\Console\Migrations;

use Heritage\Console\Command;
use Heritage\Database\Migrations\MigrationRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'migrate:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:install {--database= : The database connection to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the migration repository';

    /**
     * The repository instance.
     *
     * @var \Heritage\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Heritage\Database\Migrations\MigrationRepositoryInterface  $repository
     */
    public function __construct(MigrationRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->repository->setSource($this->input->getOption('database'));

        if (! $this->repository->repositoryExists()) {
            $this->repository->createRepository();
        }

        $this->components->info('Migration table created successfully.');
    }
}
