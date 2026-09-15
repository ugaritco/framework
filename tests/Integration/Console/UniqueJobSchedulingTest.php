<?php

namespace Heritage\Tests\Integration\Console;

use Heritage\Bus\Queueable;
use Heritage\Console\Scheduling\Schedule;
use Heritage\Contracts\Queue\ShouldBeUnique;
use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Bus\Dispatchable;
use Heritage\Queue\InteractsWithQueue;
use Heritage\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class UniqueJobSchedulingTest extends TestCase
{
    public function testJobsPushedToQueue(): void
    {
        Queue::fake();
        $this->dispatch(
            TestJob::class,
            TestJob::class,
            TestJob::class,
            TestJob::class
        );

        Queue::assertPushed(TestJob::class, 4);
    }

    public function testUniqueJobsPushedToQueue(): void
    {
        Queue::fake();
        $this->dispatch(
            UniqueTestJob::class,
            UniqueTestJob::class,
            UniqueTestJob::class,
            UniqueTestJob::class
        );

        Queue::assertPushed(UniqueTestJob::class, 1);
    }

    private function dispatch(...$jobs)
    {
        /** @var \Heritage\Console\Scheduling\Schedule $scheduler */
        $scheduler = $this->app->make(Schedule::class);
        foreach ($jobs as $job) {
            $scheduler->job($job)->name('')->everyMinute();
        }
        $events = $scheduler->events();
        foreach ($events as $event) {
            $event->run($this->app);
        }
    }
}

class TestJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Dispatchable;
}

class UniqueTestJob extends TestJob implements ShouldBeUnique
{
}
