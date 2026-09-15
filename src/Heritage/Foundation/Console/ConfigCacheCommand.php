<?php

namespace Heritage\Foundation\Console;

use Heritage\Console\Command;
use Heritage\Contracts\Console\Kernel as ConsoleKernelContract;
use Heritage\Filesystem\Filesystem;
use Heritage\Support\Arr;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'config:cache')]
class ConfigCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache file for faster configuration loading';

    /**
     * The filesystem instance.
     *
     * @var \Heritage\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config cache command instance.
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
     * @throws \LogicException
     */
    public function handle()
    {
        $this->callSilent('config:clear');

        $config = $this->getFreshConfiguration();

        $configPath = $this->ugarit->getCachedConfigPath();

        $this->files->put(
            $configPath, '<?php return '.var_export($config, true).';'.PHP_EOL
        );

        try {
            require $configPath;
        } catch (Throwable $e) {
            $this->files->delete($configPath);

            foreach (Arr::dot($config) as $key => $value) {
                try {
                    eval(var_export($value, true).';');
                } catch (Throwable $e) {
                    throw new LogicException("Your configuration files could not be serialized because the value at \"{$key}\" is non-serializable.", 0, $e);
                }
            }

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }

        $this->components->info('Configuration cached successfully.');
    }

    /**
     * Boot a fresh copy of the application configuration.
     *
     * @return array
     */
    protected function getFreshConfiguration()
    {
        $app = require $this->ugarit->bootstrapPath('app.php');

        $app->useStoragePath($this->ugarit->storagePath());

        $app->make(ConsoleKernelContract::class)->bootstrap();

        return $app['config']->all();
    }
}
