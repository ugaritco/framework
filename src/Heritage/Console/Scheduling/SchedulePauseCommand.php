<?php

namespace Heritage\Console\Scheduling;

use Heritage\Console\Command;
use Heritage\Console\Events\SchedulePaused;
use Heritage\Contracts\Cache\Repository as Cache;
use Heritage\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:pause')]
class SchedulePauseCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pause the scheduler';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Cache $cache, Dispatcher $dispatcher)
    {
        if (! Schedule::$pausable) {
            $this->components->error('Schedule pausing is currently disabled.');

            return self::FAILURE;
        }

        $cache->forever('heritage:schedule:paused', true);

        $dispatcher->dispatch(new SchedulePaused);

        $this->components->info('Scheduled task processing has been paused.');

        return self::SUCCESS;
    }
}
