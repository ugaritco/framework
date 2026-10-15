<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Ui;

use Heritage\Console\Command;
use Heritage\Filesystem\Filesystem;
use Heritage\Foundation\Console\Ui\Concerns\GeneratesUi;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Generates the frontend of an artifact's module summary: the summary page of its panel module
 * (the artifact, its totals, a card per entity and the latest activity) and the types of the
 * data ShowModuleCapability gives it, generating that capability first when it is missing.
 */
#[AsCommand(name: 'make:summary-page')]
class SummaryPageMakeCommand extends Command
{
    use GeneratesUi;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:summary-page
                    {artifact : The artifact whose panel module gets the summary page (e.g. library)}
                    {--stack= : The frontend stack (vue); detected from package.json by default}
                    {--f|force : Overwrite the files that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the summary page of an artifact\'s panel module, showing the data of its summary capability';

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
        $module = strtolower(trim((string) $this->argument('artifact')));

        if (! $this->files->isDirectory($this->ugarit->basePath("artifacts/{$module}"))) {
            $this->components->error("The artifact [{$module}] does not exist; create it with make:artifact.");

            return self::FAILURE;
        }

        if (! $this->files->isDirectory($this->modulePath($module))) {
            $this->components->error("The artifact [{$module}] has no panel module yet; create it with make:panel {$module}, which adds the summary page.");

            return self::FAILURE;
        }

        if (! $stack = $this->stack()) {
            return self::FAILURE;
        }

        $this->writeSummaryPage($stack, $module);
        $this->printReport();
        $this->refreshFrontend();

        return self::SUCCESS;
    }
}
