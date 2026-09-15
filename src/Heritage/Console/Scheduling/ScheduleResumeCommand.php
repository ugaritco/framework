<?php

namespace Heritage\Console\Scheduling;

use Heritage\Console\Command;
use Heritage\Console\Events\ScheduleResumed;
use Heritage\Contracts\Cache\Repository as Cache;
use Heritage\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:resume', aliases: ['schedule:continue'])]
class ScheduleResumeCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resume the schedule';

    /**
     * The console command name aliases.
     *
     * @var list<string>
     */
    protected $aliases = ['schedule:continue'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Cache $cache, Dispatcher $dispatcher)
    {
        $cache->forget('heritage:schedule:paused');

        $dispatcher->dispatch(new ScheduleResumed);

        $this->components->info('Scheduled task processing has resumed.');

        return self::SUCCESS;
    }
}
