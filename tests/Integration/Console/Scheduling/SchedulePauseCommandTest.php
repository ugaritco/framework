<?php

namespace Heritage\Tests\Integration\Console\Scheduling;

use Heritage\Console\Events\SchedulePaused;
use Heritage\Console\Scheduling\Schedule;
use Heritage\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class SchedulePauseCommandTest extends TestCase
{
    public function testDispatchesEvent()
    {
        Event::fake();

        $this->scribe('schedule:pause');

        Event::assertDispatched(SchedulePaused::class);
    }

    public function testFailsWhenPausingIsDisabled()
    {
        Event::fake();

        Schedule::$pausable = false;

        $this->scribe('schedule:pause')->assertFailed();

        Event::assertNotDispatched(SchedulePaused::class);

        Schedule::$pausable = true;
    }
}
