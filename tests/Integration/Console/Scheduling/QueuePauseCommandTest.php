<?php

namespace Heritage\Tests\Integration\Console\Scheduling;

use Heritage\Queue\Events\QueuePaused;
use Heritage\Queue\Events\QueuesPaused;
use Heritage\Queue\Events\QueuesResumed;
use Heritage\Queue\Worker;
use Heritage\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class QueuePauseCommandTest extends TestCase
{
    public function testDispatchesEvent()
    {
        Event::fake();

        $this->scribe('queue:pause default');

        Event::assertDispatched(QueuePaused::class);
    }

    public function testPauseAllDispatchesEvent()
    {
        Event::fake();

        $this->scribe('queue:pause --all');

        Event::assertDispatched(QueuesPaused::class);
    }

    public function testResumeAllDispatchesEvent()
    {
        Event::fake();

        $this->scribe('queue:resume --all');

        Event::assertDispatched(QueuesResumed::class);
    }

    public function testDisabledError()
    {
        Event::fake();

        Worker::$pausable = false;

        $this->scribe('queue:pause default');

        Event::assertNotDispatched(QueuePaused::class);

        Worker::$pausable = true;
    }
}
