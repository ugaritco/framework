<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Ui;

use Heritage\Console\Command;
use Heritage\Filesystem\Filesystem;
use Heritage\Foundation\Console\Ui\Concerns\GeneratesUi;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Generates the frontend of an artifact's summary: the summary page in its panel folder (the
 * artifact, its totals, a card per entity and the latest activity) and the types of the data
 * ShowArtifactCapability gives it, generating that capability first when it is missing.
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
                    {artifact : The artifact whose panel gets the summary page (e.g. library)}
                    {--stack= : The frontend stack (vue); detected from package.json by default}
                    {--f|force : Overwrite the files that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the summary page of an artifact\'s panel, showing the data of its summary capability';

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
        $artifact = strtolower(trim((string) $this->argument('artifact')));

        if (! $this->files->isDirectory($this->ugarit->basePath("artifacts/{$artifact}"))) {
            $this->components->error("The artifact [{$artifact}] does not exist; create it with make:artifact.");

            return self::FAILURE;
        }

        if (! $this->files->isDirectory($this->artifactPath($artifact))) {
            $this->components->error("The artifact [{$artifact}] has no panel yet; create it with make:panel {$artifact}, which adds the summary page.");

            return self::FAILURE;
        }

        if (! $stack = $this->stack()) {
            return self::FAILURE;
        }

        $this->writeSummaryPage($stack, $artifact);
        $this->printReport();
        $this->refreshFrontend();

        return self::SUCCESS;
    }
}
