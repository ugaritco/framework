<?php

namespace Heritage\Console\Scheduling;

use Heritage\Console\Command;
use Heritage\Console\Events\ScheduledBackgroundTaskFinished;
use Heritage\Contracts\Events\Dispatcher;
use Heritage\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:finish')]
class ScheduleFinishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:finish {id} {code=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle the completion of a scheduled command';

    /**
     * Indicates whether the command should be shown in the Scribe command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Execute the console command.
     *
     * @param  \Heritage\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function handle(Schedule $schedule)
    {
        (new Collection($schedule->events()))
            ->filter(fn ($value) => $value->mutexName() == $this->argument('id'))
            ->each(function ($event) {
                $event->finish($this->ugarit, $this->argument('code'));

                $this->ugarit->make(Dispatcher::class)->dispatch(new ScheduledBackgroundTaskFinished($event));
            });
    }
}
