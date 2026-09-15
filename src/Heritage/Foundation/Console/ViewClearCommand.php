<?php

namespace Heritage\Foundation\Console;

use Heritage\Console\Command;
use Heritage\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'view:clear')]
class ViewClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all compiled view files';

    /**
     * The filesystem instance.
     *
     * @var \Heritage\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config clear command instance.
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
     *
     * @throws \RuntimeException
     */
    public function handle()
    {
        $path = $this->ugarit['config']['view.compiled'];

        if (! $path) {
            throw new RuntimeException('View path not found.');
        }

        $this->ugarit['view.engine.resolver']
            ->resolve('blade')
            ->forgetCompiledOrNotExpired();

        foreach ($this->files->glob("{$path}/*") as $view) {
            if ($this->files->isDirectory($view)) {
                $this->files->deleteDirectory($view);
            } else {
                $this->files->delete($view);
            }
        }

        $this->components->info('Compiled views cleared successfully.');
    }
}
