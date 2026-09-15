<?php

namespace Heritage\Tests\Integration\Console\Scheduling;

use Heritage\Console\Events\ScheduleResumed;
use Heritage\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class ScheduleResumeCommandTest extends TestCase
{
    public function testDispatchesEvent()
    {
        Event::fake();

        $this->scribe('schedule:resume');

        Event::assertDispatched(ScheduleResumed::class);
    }
}
