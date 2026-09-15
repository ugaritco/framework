<?php

namespace Heritage\Queue\Console;

use Heritage\Console\Command;
use Heritage\Contracts\Cache\Repository as Cache;
use Heritage\Support\InteractsWithTime;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:restart')]
class RestartCommand extends Command
{
    use InteractsWithTime;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:restart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart queue worker daemons after their current job';

    /**
     * The cache store implementation.
     *
     * @var \Heritage\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new queue restart command.
     *
     * @param  \Heritage\Contracts\Cache\Repository  $cache
     */
    public function __construct(Cache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->cache->forever('heritage:queue:restart', $this->currentTime());

        $this->components->info('Broadcasting queue restart signal.');
    }
}
