<?php

namespace Heritage\Foundation\Console;

use Heritage\Console\Command;
use Heritage\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'route:clear')]
class RouteClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the route cache file';

    /**
     * The filesystem instance.
     *
     * @var \Heritage\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new route clear command instance.
     *
     * @param  \Heritage\Filesystem\Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->files->delete($this->ugarit->getCachedRoutesPath());

        $this->components->info('Route cache cleared successfully.');
    }
}
