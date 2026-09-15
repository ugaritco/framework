<?php

namespace Heritage\Console\Events;

use Heritage\Console\Scheduling\Event;

class ScheduledBackgroundTaskFinished
{
    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Console\Scheduling\Event  $task  The scheduled event that ran.
     */
    public function __construct(
        public Event $task,
    ) {
    }
}
