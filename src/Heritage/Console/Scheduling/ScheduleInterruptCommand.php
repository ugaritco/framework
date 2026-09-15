<?php

namespace Heritage\Console\Scheduling;

use Heritage\Console\Command;
use Heritage\Contracts\Cache\Repository as Cache;
use Heritage\Support\Facades\Date;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:interrupt')]
class ScheduleInterruptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:interrupt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interrupt the current schedule run';

    /**
     * The cache store implementation.
     *
     * @var \Heritage\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new schedule interrupt command.
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
        $this->cache->put('heritage:schedule:interrupt', true, Date::now()->endOfMinute());

        $this->components->info('Broadcasting schedule interrupt signal.');
    }
}
